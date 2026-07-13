<?php

namespace Drupal\shh_pricing_comparison\Plugin\Block;

use CommerceGuys\Intl\Formatter\CurrencyFormatterInterface;
use Drupal\commerce_price\Price;
use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\shh_facility_credits\FacilityPricingHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Pricing at a glance, for the homepage (task 0051, section 10).
 *
 * Three ways to pay, side by side: a single slot, a credit pack, or the
 * multi-facility bundle. **Every number is computed from live config**,
 * never written into the page — the same rule tasks 0020 and 0023 set,
 * and for good reason: task 0020 found the facilities' price frequency
 * had silently drifted, making real bookings cost 0,00 DKK. A homepage
 * that hardcoded "50 DKK" would have gone on lying about it.
 */
#[Block(
  id: 'shh_pricing_summary',
  admin_label: new TranslatableMarkup('SHH pricing at a glance'),
)]
class PricingSummaryBlock extends BlockBase implements ContainerFactoryPluginInterface {

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected CurrencyFormatterInterface $currencyFormatter,
    protected FacilityPricingHelper $pricingHelper,
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
      $container->get('entity_type.manager'),
      $container->get('commerce_price.currency_formatter'),
      $container->get('shh_facility_credits.pricing_helper'),
      $container->get('config.factory'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $nodes = $this->entityTypeManager->getStorage('node')->loadByProperties([
      'type' => 'bookable_facility',
      'status' => 1,
    ]);
    if (!$nodes) {
      return [];
    }

    // The cheapest slot on offer, so "from X" is true rather than
    // flattering.
    $cheapest = NULL;
    $slot_minutes = 30;
    foreach ($nodes as $node) {
      $price = $this->pricingHelper->getSlotPrice($node);
      if (!$price) {
        continue;
      }
      if (!$cheapest || $price->lessThan($cheapest)) {
        $cheapest = $price;
        $slot_minutes = (int) $node->get('field_slot_duration_minutes')->value;
      }
    }
    if (!$cheapest) {
      return [];
    }

    $pack_price = $this->pricingHelper->getPackPrice($cheapest);

    $bundle_settings = $this->configFactory->get('shh_facility_bundle_discount.settings');
    $discount = $bundle_settings->get('discount_amount');

    $cards = [
      [
        'title' => $this->t('One slot at a time'),
        'price' => $this->format($cheapest),
        'note' => $this->t('From, per @minutes-minute slot. Pay as you ride, no commitment.', ['@minutes' => $slot_minutes]),
      ],
      [
        'title' => $this->t('@count-session credit pack', ['@count' => FacilityPricingHelper::PACK_SIZE]),
        'price' => $this->format($pack_price),
        'note' => $this->t('@discount% off, for one facility. Credits never expire — redeem them whenever you ride.', [
          '@discount' => FacilityPricingHelper::DISCOUNT_PERCENTAGE,
        ]),
      ],
      [
        'title' => $this->t('All three, same slot'),
        'price' => $this->t('−@amount', ['@amount' => $this->format($this->price($discount))]),
        'note' => $this->t('Book the oval track, manège and lunge ring for the same time and the discount comes off automatically at checkout.'),
      ],
    ];

    $rendered = [];
    foreach ($cards as $card) {
      $rendered[] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => [
            'flex', 'flex-col', 'gap-2', 'border', 'border-border',
            'rounded-xl', 'p-6', 'text-center',
          ],
        ],
        'title' => [
          '#type' => 'component',
          '#component' => 'hestehoj:heading',
          '#props' => [
            'heading_text' => (string) $card['title'],
            'level' => 3,
            'text_size' => 'heading-responsive-xl',
            'text_color' => 'default',
            'align' => 'center',
          ],
        ],
        'price' => [
          '#type' => 'component',
          '#component' => 'hestehoj:heading',
          '#props' => [
            'heading_text' => (string) $card['price'],
            'level' => 4,
            'text_size' => 'heading-responsive-3xl',
            'text_color' => 'primary',
            'align' => 'center',
          ],
        ],
        'note' => [
          '#type' => 'component',
          '#component' => 'hestehoj:text',
          '#props' => [
            'text' => (string) $card['note'],
            'text_size' => 'normal',
            'text_color' => 'default',
          ],
        ],
      ];
    }

    return [
      'grid' => [
        '#type' => 'container',
        '#attributes' => ['class' => ['grid', 'grid-cols-1', 'gap-6', 'md:grid-cols-3']],
        'cards' => $rendered,
      ],
      'more' => [
        '#type' => 'container',
        '#attributes' => ['class' => ['mt-8', 'flex', 'justify-center']],
        'link' => [
          '#type' => 'component',
          '#component' => 'hestehoj:button',
          '#props' => [
            'label' => $this->t('Compare pricing side by side'),
            'href' => Url::fromRoute('shh_pricing_comparison.comparison')->toString(),
            'variant' => 'secondary',
            'size' => 'medium',
            'icon' => 'arrow-right',
            'icon_first' => FALSE,
            'disabled' => FALSE,
            'mobile_width' => FALSE,
          ],
        ],
      ],
      '#cache' => [
        'tags' => [
          'node_list:bookable_facility',
          'config:shh_facility_bundle_discount.settings',
        ],
      ],
    ];
  }

  /**
   * Formats a price for display.
   */
  protected function format($price): string {
    return $this->currencyFormatter->format($price->getNumber(), $price->getCurrencyCode());
  }

  /**
   * The bundle discount as a Price, from its config array.
   */
  protected function price(array $data): Price {
    return new Price((string) $data['number'], $data['currency_code']);
  }

}
