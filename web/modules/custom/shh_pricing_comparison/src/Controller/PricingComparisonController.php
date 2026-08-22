<?php

namespace Drupal\shh_pricing_comparison\Controller;

use CommerceGuys\Intl\Formatter\CurrencyFormatterInterface;
use Drupal\commerce_price\Price;
use Drupal\commerce_price\RounderInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\node\NodeInterface;
use Drupal\shh_facility_credits\FacilityPricingHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Public pricing comparison page.
 *
 * See docs/project-management/tasks/0023-pricing-comparison-page.md. Three
 * genuinely different ways to pay for a facility booking exist
 * (single slot, 0016; 10-session credit pack, 0018; same-timeframe
 * three-facility bundle, 0017) but were never shown side by side anywhere
 * — every number here is pulled live from FacilityPricingHelper (shared
 * with the facilities overview page, 0020) and
 * shh_facility_bundle_discount's own config, never hardcoded, so it can't
 * silently drift from the actual checkout behaviour.
 */
class PricingComparisonController extends ControllerBase {

  public function __construct(
    protected CurrencyFormatterInterface $currencyFormatter,
    protected FacilityPricingHelper $pricingHelper,
    protected RounderInterface $rounder,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('commerce_price.currency_formatter'),
      $container->get('shh_facility_credits.pricing_helper'),
      $container->get('commerce_price.rounder'),
    );
  }

  /**
   * Builds the comparison page: a table plus a worked bundle example.
   */
  public function comparison(): array {
    $node_storage = $this->entityTypeManager()->getStorage('node');
    $ids = $node_storage->getQuery()
      ->condition('type', 'bookable_facility')
      ->condition('status', TRUE)
      ->sort('title', 'ASC')
      ->accessCheck(TRUE)
      ->execute();

    $build = [];

    if (!$ids) {
      $build['empty'] = [
        '#markup' => '<p>' . $this->t('No facilities are available to compare right now.') . '</p>',
      ];
      return $build;
    }

    /** @var \Drupal\node\NodeInterface[] $nodes */
    $nodes = $node_storage->loadMultiple($ids);

    $build['#attached'] = [];
    shh_common_attach_meta_tags(
      $build['#attached'],
      (string) $this->t('Pricing comparison'),
      (string) $this->t('Compare facility booking prices at Stutteri Hestehøj — single slot, 10-session credit pack, or the same-slot multi-facility bundle, side by side.'),
    );

    $build['table'] = $this->buildTable($nodes);
    $build['bundle_example'] = $this->buildBundleExample($nodes);

    return $build;
  }

  /**
   * Builds the single-slot / credit-pack comparison table.
   *
   * @param \Drupal\node\NodeInterface[] $nodes
   *   The bookable facility nodes.
   */
  protected function buildTable(array $nodes): array {
    $header = [
      $this->t('Facility'),
      $this->t('Single slot'),
      $this->t('@count-session pack', ['@count' => $this->pricingHelper->getPackSize()]),
      $this->t('Effective price/slot in a pack'),
    ];

    $rows = [];
    foreach ($nodes as $node) {
      $slot_price = $this->pricingHelper->getSlotPrice($node);
      if (!$slot_price) {
        $rows[] = [
          $node->label(),
          ['data' => $this->t('Not set up for fixed-length slots'), 'colspan' => 3],
        ];
        continue;
      }
      $pack_price = $this->pricingHelper->getPackPrice($slot_price);
      $effective_per_slot = $this->rounder->round($pack_price->divide((string) $this->pricingHelper->getPackSize()));

      $rows[] = [
        $node->label(),
        $this->formatPrice($slot_price),
        $this->formatPrice($pack_price) . ' ' . $this->t('(@discount% off)', ['@discount' => $this->pricingHelper->getDiscountPercentage()]),
        $this->formatPrice($effective_per_slot),
      ];
    }

    return [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#attributes' => ['class' => ['shh-pricing-table', 'w-full', 'mb-10']],
    ];
  }

  /**
   * Builds the worked three-facility same-timeframe bundle example.
   *
   * @param \Drupal\node\NodeInterface[] $nodes
   *   The bookable facility nodes, keyed by node ID.
   */
  protected function buildBundleExample(array $nodes): array {
    $bundle_settings = $this->config('shh_facility_bundle_discount.settings');
    $product_ids = $bundle_settings->get('product_ids') ?? [];
    $discount_amount_data = $bundle_settings->get('discount_amount');
    $discount_amount = new Price((string) $discount_amount_data['number'], $discount_amount_data['currency_code']);

    $bundle_nodes = array_filter($nodes, function (NodeInterface $node) use ($product_ids) {
      if (!$node->hasField('field_product') || $node->get('field_product')->isEmpty()) {
        return FALSE;
      }
      return in_array((int) $node->get('field_product')->target_id, $product_ids, TRUE);
    });

    if (count($bundle_nodes) < 2) {
      // Nothing meaningful to show if the bundle isn't configured across
      // at least two facilities on this site.
      return [];
    }

    $total = new Price('0', $discount_amount->getCurrencyCode());
    $names = [];
    foreach ($bundle_nodes as $node) {
      $slot_price = $this->pricingHelper->getSlotPrice($node);
      if (!$slot_price) {
        continue;
      }
      $total = $total->add($slot_price);
      $names[] = $node->label();
    }
    $discounted_total = $this->rounder->round($total->subtract($discount_amount));

    $build = [
      '#type' => 'container',
      '#attributes' => ['class' => ['max-w-3xl']],
    ];
    $build['heading'] = ['#markup' => '<h2>' . $this->t('Book them all in the same slot and save') . '</h2>'];
    $build['example'] = [
      '#markup' => '<p>' . $this->t('Booking @names all for the exact same time slot: normally @full, now @discounted (@amount off automatically at checkout).', [
        '@names' => implode(', ', $names),
        '@full' => $this->formatPrice($total),
        '@discounted' => $this->formatPrice($discounted_total),
        '@amount' => $this->formatPrice($discount_amount),
      ]) . '</p>',
    ];

    return $build;
  }

  /**
   * Formats a Price as a human-readable currency string.
   */
  protected function formatPrice(Price $price): string {
    return $this->currencyFormatter->format($price->getNumber(), $price->getCurrencyCode());
  }

}
