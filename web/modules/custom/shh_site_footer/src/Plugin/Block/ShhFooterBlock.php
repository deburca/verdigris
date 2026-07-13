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
        // The slot's own wrapper is `md:flex md:justify-end`, so give it a
        // single child and stack inside it — two children would sit side
        // by side on the same row.
        'footer_utility_last' => [
          '#type' => 'html_tag',
          '#tag' => 'div',
          '#attributes' => ['class' => ['flex', 'flex-col', 'gap-1', 'md:items-end']],
          'copyright' => [
            '#type' => 'html_tag',
            '#tag' => 'div',
            '#attributes' => ['class' => ['md:text-right']],
            '#value' => '© ' . date('Y') . ' ' . $site_name,
          ],
          'attribution' => $this->buildAttribution(),
        ],
      ],
      '#cache' => ['tags' => ['config:system.site']],
    ];
  }

  /**
   * Builds the "Made with ♥ by verdigris.nu with a sprinkle of ✦" line.
   *
   * Phosphor icons (the pack the theme already vendors — no CDN, per
   * task 0009) rather than emoji: they inherit `currentColor`, so they
   * take the footer's own text colour in both light and dark mode,
   * where emoji glyphs render differently on every platform and cannot
   * be recoloured.
   *
   * Accessibility: the SVGs are decorative (the icon component sets
   * `aria-hidden` when no alt is given), so each carries a
   * visually-hidden word — a screen reader hears "Made with love by
   * verdigris.nu with a sprinkle of AI", not "heart … sparkle".
   *
   * SHH-only by construction: this lives in the shh_site_footer block
   * plugin, not in the theme's footer SDC, so the other sites — which
   * compose their footers through SDC configuration — are untouched.
   */
  protected function buildAttribution(): array {
    return [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => ['class' => ['shh-attribution', 'md:text-right']],
      'text' => [
        '#type' => 'inline_template',
        // One flowing sentence: the icons sit inline between words, so
        // this must NOT be a flex container (that turns every phrase
        // into its own column) and the icons must not be block-level —
        // hence core's `icon` element, which emits the bare <svg>,
        // rather than the theme's icon SDC, which wraps it in a div.
        '#template' => '{{ "Made with"|t }} <span class="inline-block align-text-bottom">{{ heart }}</span><span class="visually-hidden">{{ "love"|t }}</span> {{ "by"|t }} <a href="{{ url }}" class="underline underline-offset-2">verdigris.nu</a> {{ "with a sprinkle of"|t }} <span class="inline-block align-text-bottom">{{ sparkle }}</span><span class="visually-hidden">{{ "AI"|t }}</span>',
        '#context' => [
          'url' => 'https://verdigris.nu/',
          'heart' => $this->buildIcon('heart'),
          'sparkle' => $this->buildIcon('sparkle'),
        ],
      ],
    ];
  }

  /**
   * One inline Phosphor icon, sized to the footer's small type.
   */
  protected function buildIcon(string $icon): array {
    return [
      '#type' => 'icon',
      '#pack_id' => 'phosphor',
      '#icon_id' => $icon,
      '#settings' => ['size' => 16],
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
