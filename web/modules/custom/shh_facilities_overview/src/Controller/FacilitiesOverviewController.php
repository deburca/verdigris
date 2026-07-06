<?php

namespace Drupal\shh_facilities_overview\Controller;

use CommerceGuys\Intl\Formatter\CurrencyFormatterInterface;
use Drupal\commerce_price\Price;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\Markup;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\shh_facility_credits\FacilityPricingHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Public "Book a facility" overview page.
 *
 * See docs/project-management/tasks/0020-facilities-overview-page.md.
 * Previously no page listed the three bookable facilities together — a
 * rider needed a direct link to /oval-track, /manege, or /lunge-ring
 * individually, and the bundle discount (0017) / credit packs (0018)
 * were undiscoverable except by landing on the one facility page that
 * happens to mention them.
 */
class FacilitiesOverviewController extends ControllerBase {

  public function __construct(
    protected CurrencyFormatterInterface $currencyFormatter,
    protected FacilityPricingHelper $pricingHelper,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('commerce_price.currency_formatter'),
      $container->get('shh_facility_credits.pricing_helper'),
    );
  }

  /**
   * Builds the overview page.
   *
   * One card per facility, plus an explainer for the bundle discount and
   * credit packs.
   */
  public function overview(): array {
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
        '#markup' => '<p>' . $this->t('No facilities are available to book right now.') . '</p>',
      ];
      return $build;
    }

    /** @var \Drupal\node\NodeInterface[] $nodes */
    $nodes = $node_storage->loadMultiple($ids);
    $cards = [];
    foreach ($nodes as $node) {
      $cards[] = $this->buildCard($node);
    }

    $build['grid'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['grid', 'grid-cols-1', 'gap-6', 'sm:grid-cols-2', 'lg:grid-cols-3', 'mb-10'],
      ],
      'cards' => $cards,
    ];

    $build['bundle_and_credits'] = $this->buildExplainer($nodes);

    // Task 0023's own acceptance criteria says to link the pricing
    // comparison page from here once it exists.
    $build['pricing_link'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['mb-3']],
      '#weight' => 20,
      'link' => [
        '#type' => 'component',
        '#component' => 'hestehoj:button',
        '#props' => [
          'variant' => 'secondary',
          'size' => 'medium',
          'label' => $this->t('Compare pricing side by side'),
          'href' => Url::fromRoute('shh_pricing_comparison.comparison')->toString(),
        ],
      ],
    ];

    return $build;
  }

  /**
   * Builds a single hestehoj:card render array for one facility.
   */
  protected function buildCard(NodeInterface $node): array {
    $summary_parts = [];

    if ($node->hasField('field_facility_kind') && !$node->get('field_facility_kind')->isEmpty()) {
      $allowed_values = $node->get('field_facility_kind')->getFieldDefinition()
        ->getFieldStorageDefinition()->getSetting('allowed_values');
      $value = $node->get('field_facility_kind')->value;
      $summary_parts[] = $allowed_values[$value] ?? $value;
    }

    if ($node->hasField('field_indoor') && !$node->get('field_indoor')->isEmpty()) {
      $summary_parts[] = $node->get('field_indoor')->value ? $this->t('Indoor') : $this->t('Outdoor');
    }

    if ($node->hasField('field_capacity') && !$node->get('field_capacity')->isEmpty()) {
      $summary_parts[] = $this->formatPlural(
        (int) $node->get('field_capacity')->value,
        '1 rider',
        '@count riders',
      );
    }

    $slot_price = $this->pricingHelper->getSlotPrice($node);
    if ($slot_price) {
      $slot_minutes = (int) $node->get('field_slot_duration_minutes')->value;
      $summary_parts[] = $this->t('@price / @minutes min', [
        '@price' => $this->currencyFormatter->format($slot_price->getNumber(), $slot_price->getCurrencyCode()),
        '@minutes' => $slot_minutes,
      ]);
    }

    return [
      '#type' => 'component',
      '#component' => 'hestehoj:card',
      '#props' => [
        'heading_text' => $node->label(),
        'orientation' => 'vertical',
        'style' => 'framed',
        'url' => $node->toUrl()->toString(),
        'text' => implode(' · ', array_filter($summary_parts)),
      ],
    ];
  }

  /**
   * Builds the bundle-discount / credit-pack explainer section.
   *
   * Every number here is pulled from live config/constants (never
   * hardcoded) so it can't silently drift from the actual checkout
   * behaviour — same requirement task 0023's pricing comparison page
   * states explicitly, applied here too since this page makes the same
   * kind of claim.
   *
   * @param \Drupal\node\NodeInterface[] $nodes
   *   The bookable facility nodes.
   */
  protected function buildExplainer(array $nodes): array {
    $bundle_settings = $this->config('shh_facility_bundle_discount.settings');
    $discount_amount_data = $bundle_settings->get('discount_amount');
    $bundle_discount = new Price((string) $discount_amount_data['number'], $discount_amount_data['currency_code']);
    $bundle_discount_formatted = $this->currencyFormatter->format($bundle_discount->getNumber(), $bundle_discount->getCurrencyCode());

    $build = [
      '#type' => 'container',
      '#attributes' => ['class' => ['max-w-3xl']],
    ];

    // Markup::create() is required here, not just string concatenation:
    // the "text" prop is documented (contentMediaType: text/html) to
    // accept HTML, but a plain string is auto-escaped like any other
    // Twig variable — concatenating with a TranslatableMarkup object
    // casts it straight back to a plain string, losing "safe" status.
    // Wrapping the final HTML in Markup::create() is the standard
    // #markup-equivalent way to mark genuinely-trusted, code-built HTML
    // (not user input) as safe to render unescaped.
    $bundle_text = Markup::create(
      '<h3>' . $this->t('Book all three facilities together and save') . '</h3><p>' . $this->t('Book the Oval Track, Manège, and Lunge Ring all for the exact same time slot, and @amount is taken off the combined total automatically at checkout — no code needed.', [
        '@amount' => $bundle_discount_formatted,
      ]) . '</p>',
    );
    $build['bundle'] = [
      '#type' => 'component',
      '#component' => 'hestehoj:text',
      '#props' => [
        'text_color' => 'default',
        'text_size' => 'normal',
        'text' => $bundle_text,
      ],
      '#weight' => 0,
    ];

    $credits_text = Markup::create(
      '<h3>' . $this->t('Save with a credit pack') . '</h3><p>' . $this->t('Buy @count reservations for one facility at once, at @discount% off, and redeem them one at a time whenever you like — they never expire.', [
        '@count' => FacilityPricingHelper::PACK_SIZE,
        '@discount' => FacilityPricingHelper::DISCOUNT_PERCENTAGE,
      ]) . '</p>',
    );
    $build['credits'] = [
      '#type' => 'component',
      '#component' => 'hestehoj:text',
      '#props' => [
        'text_color' => 'default',
        'text_size' => 'normal',
        'text' => $credits_text,
      ],
      '#weight' => 10,
    ];

    return $build;
  }

}
