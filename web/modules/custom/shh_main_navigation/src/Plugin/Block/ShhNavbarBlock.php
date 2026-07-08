<?php

namespace Drupal\shh_main_navigation\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Menu\MenuLinkTreeInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Renders the site header via the theme's slotted navbar SDC (task 0032).
 *
 * Replaces the plain system_menu_block placement from task 0019: same
 * `main` menu (rendered through the theme's own menu--main.html.twig,
 * so per-user access filtering keeps working — the "Log in / Register"
 * link still hides itself for authenticated riders), now composed into
 * `hestehoj:navbar`'s navigation slot with the site-name wordmark in
 * the logo slot. The links (CTA) slot is deliberately overridden empty:
 * per the client direction on 0032, calls-to-action belong in page
 * content, not page furniture — and leaving the slot unset would render
 * the component's example.com placeholder buttons.
 */
#[Block(
  id: 'shh_navbar',
  admin_label: new TranslatableMarkup('SHH navbar (hestehoj:navbar)'),
)]
class ShhNavbarBlock extends BlockBase implements ContainerFactoryPluginInterface {

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected MenuLinkTreeInterface $menuLinkTree,
    protected ConfigFactoryInterface $configFactory,
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
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $parameters = $this->menuLinkTree->getCurrentRouteMenuTreeParameters('main');
    $parameters->setMaxDepth(2)->onlyEnabledLinks();
    $tree = $this->menuLinkTree->load('main', $parameters);
    $tree = $this->menuLinkTree->transform($tree, [
      ['callable' => 'menu.default_tree_manipulators:checkAccess'],
      ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
    ]);

    $site_name = $this->configFactory->get('system.site')->get('name');
    $logo = [
      '#type' => 'html_tag',
      '#tag' => 'a',
      '#attributes' => [
        'href' => Url::fromRoute('<front>')->toString(),
        'class' => ['inline-block'],
        'rel' => 'home',
      ],
      'wordmark' => [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#attributes' => ['class' => ['heading-responsive-4xl', 'font-[var(--hgc-font-weight-h1)]']],
        '#value' => $site_name,
      ],
      '#cache' => ['tags' => ['config:system.site']],
    ];

    return [
      '#type' => 'component',
      '#component' => 'hestehoj:navbar',
      '#props' => [
        'menu_align' => 'center',
      ],
      '#slots' => [
        'logo' => $logo,
        'navigation' => $this->menuLinkTree->build($tree),
        'links' => ['#plain_text' => ''],
      ],
    ];
  }

}
