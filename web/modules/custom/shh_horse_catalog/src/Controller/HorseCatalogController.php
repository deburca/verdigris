<?php

namespace Drupal\shh_horse_catalog\Controller;

use CommerceGuys\Intl\Formatter\CurrencyFormatterInterface;
use Drupal\commerce_product\Entity\ProductVariationInterface;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Public "Horses for sale" catalog page.
 *
 * See docs/project-management/tasks/0019-horse-catalog-page.md. There was
 * previously no way to browse horses at all without a direct
 * /product/{id} link (the admin product list at /admin/commerce/products
 * is staff-only) — every "horse sale" verification in this project's
 * history landed directly on an individual product page.
 */
class HorseCatalogController extends ControllerBase {

  public function __construct(
    protected CurrencyFormatterInterface $currencyFormatter,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('commerce_price.currency_formatter'),
    );
  }

  /**
   * Builds the catalog page: one card per horse currently for sale.
   */
  public function catalog(): array {
    $variation_storage = $this->entityTypeManager()->getStorage('commerce_product_variation');
    $ids = $variation_storage->getQuery()
      ->condition('type', 'horse')
      ->condition('field_sale_state', 'available')
      ->condition('status', TRUE)
      ->accessCheck(TRUE)
      ->execute();

    $build = [];

    if (!$ids) {
      $build['empty'] = [
        '#markup' => '<p>' . $this->t('There are no horses for sale right now — please check back soon.') . '</p>',
      ];
      return $build;
    }

    /** @var \Drupal\commerce_product\Entity\ProductVariationInterface[] $variations */
    $variations = $variation_storage->loadMultiple($ids);

    // Multiple variations could in principle belong to the same product;
    // this platform only ever uses one variation per horse product (see
    // task 0011), but dedupe by product ID defensively rather than
    // assume it and show the same horse twice if that ever changes.
    $seen_product_ids = [];
    $cards = [];
    foreach ($variations as $variation) {
      $product = $variation->getProduct();
      if (!$product || isset($seen_product_ids[$product->id()])) {
        continue;
      }
      $seen_product_ids[$product->id()] = TRUE;
      $cards[] = $this->buildCard($variation);
    }

    $build['grid'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['grid', 'grid-cols-1', 'gap-6', 'sm:grid-cols-2', 'lg:grid-cols-3'],
      ],
      'cards' => $cards,
    ];

    return $build;
  }

  /**
   * Builds a single hestehoj:card render array for one horse variation.
   */
  protected function buildCard(ProductVariationInterface $variation): array {
    $product = $variation->getProduct();

    $summary_parts = [];
    if ($variation->hasField('field_breed') && !$variation->get('field_breed')->isEmpty()) {
      $summary_parts[] = $variation->get('field_breed')->value;
    }
    if ($variation->hasField('field_gaits')) {
      // field_gaits is a plain list_string field (see task 0014) — resolve
      // machine-name values to human-readable labels (task 0031's shared
      // helper, factored out of this controller).
      $gait_labels = shh_common_list_string_labels($variation->get('field_gaits'));
      if ($gait_labels) {
        $summary_parts[] = implode(', ', $gait_labels);
      }
    }
    $summary_parts[] = $this->currencyFormatter->format(
      $variation->getPrice()->getNumber(),
      $variation->getPrice()->getCurrencyCode(),
    );

    $props = [
      'heading_text' => $product->label(),
      'orientation' => 'vertical',
      'style' => 'framed',
      'url' => $product->toUrl()->toString(),
      'text' => implode(' · ', array_filter($summary_parts)),
    ];

    // Shared image-media-to-props helper (task 0031, factored out of
    // this controller's original private copy).
    $media_props = shh_common_image_media_props($variation, 'field_media');
    if ($media_props) {
      $props['media'] = $media_props;
    }

    return [
      '#type' => 'component',
      '#component' => 'hestehoj:card',
      '#props' => $props,
    ];
  }

}
