<?php

namespace Drupal\shh_site_footer\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Menu\MenuLinkTreeInterface;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Renders the site footer via the theme's slotted footer SDC (task 0032).
 *
 * Replaces the plain system_menu_block placement from task 0027. Slot
 * composition per the client direction on 0032 — user-agnostic
 * information only, no calls-to-action (those belong in page content):
 * - Branding & Social: site-name wordmark, the stable's address and
 *   email (read live from the default Commerce store, the single place
 *   that data already exists), and the `social` menu (placeholder
 *   Facebook/Instagram links until the client confirms real profiles)
 * - Call to action: deliberately empty
 * - Utility links: the `footer` menu (Privacy policy, 0027's
 *   Contact us)
 * - Copyright text: © {current year} {site name}
 * A leaflet/OpenStreetMap embed for the address is a recorded future
 * enhancement on task 0032, not built here.
 */
#[Block(
  id: 'shh_footer',
  admin_label: new TranslatableMarkup('SHH footer (hestehoj:footer)'),
)]
class ShhFooterBlock extends BlockBase implements ContainerFactoryPluginInterface {

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected MenuLinkTreeInterface $menuLinkTree,
    protected ConfigFactoryInterface $configFactory,
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('menu.link_tree'),
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $site_name = $this->configFactory->get('system.site')->get('name');

    $footer_first = [
      'wordmark' => [
        '#type' => 'html_tag',
        '#tag' => 'a',
        '#attributes' => [
          'href' => Url::fromRoute('<front>')->toString(),
          'class' => ['inline-block'],
          'rel' => 'home',
        ],
        'name' => [
          '#type' => 'html_tag',
          '#tag' => 'div',
          '#attributes' => ['class' => ['heading-responsive-2xl', 'font-[var(--hgc-font-weight-h1)]']],
          '#value' => $site_name,
        ],
      ],
      'contact' => $this->buildContactDetails(),
      'social' => $this->buildMenu('social'),
    ];

    return [
      '#type' => 'component',
      '#component' => 'hestehoj:footer',
      '#props' => [
        'align' => TRUE,
      ],
      '#slots' => [
        'footer_first' => $footer_first,
        'footer_last' => ['#plain_text' => ''],
        'footer_utility_first' => $this->buildMenu('footer'),
        'footer_utility_last' => [
          '#type' => 'html_tag',
          '#tag' => 'div',
          '#value' => '© ' . date('Y') . ' ' . $site_name,
        ],
      ],
      '#cache' => ['tags' => ['config:system.site']],
    ];
  }

  /**
   * Renders a menu tree for a footer slot (access-checked and sorted).
   */
  protected function buildMenu(string $menu_name): array {
    $parameters = (new MenuTreeParameters())->setMaxDepth(1)->onlyEnabledLinks();
    $tree = $this->menuLinkTree->load($menu_name, $parameters);
    $tree = $this->menuLinkTree->transform($tree, [
      ['callable' => 'menu.default_tree_manipulators:checkAccess'],
      ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
    ]);
    return $tree ? $this->menuLinkTree->build($tree) : [];
  }

  /**
   * Address + email from the default Commerce store, if one exists.
   *
   * The store is where this data already lives (set up in task 0011) —
   * reading it live keeps a single source of truth instead of a second
   * hand-maintained copy in the footer.
   */
  protected function buildContactDetails(): array {
    $stores = $this->entityTypeManager->getStorage('commerce_store')
      ->loadByProperties(['is_default' => TRUE]);
    $store = reset($stores);
    if (!$store) {
      return [];
    }
    /** @var \Drupal\commerce_store\Entity\StoreInterface $store */
    $address = $store->getAddress();
    $lines = array_filter([
      trim((string) $address->getAddressLine1()),
      trim((string) $address->getAddressLine2()),
      trim($address->getPostalCode() . ' ' . $address->getLocality()),
    ]);

    $build = [
      '#type' => 'html_tag',
      '#tag' => 'address',
      '#attributes' => ['class' => ['not-italic', 'flex', 'flex-col', 'gap-1']],
    ];
    foreach (array_values($lines) as $i => $line) {
      $build['line_' . $i] = [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => $line,
      ];
    }
    if ($store->getEmail()) {
      $build['email'] = [
        '#type' => 'html_tag',
        '#tag' => 'a',
        '#attributes' => ['href' => 'mailto:' . $store->getEmail()],
        '#value' => $store->getEmail(),
      ];
    }
    CacheableMetadata::createFromRenderArray($build)
      ->addCacheableDependency($store)
      ->applyTo($build);
    return $build;
  }

}
