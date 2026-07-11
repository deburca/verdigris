<?php

namespace Drupal\shh_feed_catalog\Controller;

use CommerceGuys\Intl\Formatter\CurrencyFormatterInterface;
use Drupal\commerce_product\Entity\ProductInterface;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Public "Feed & bedding" catalog page.
 *
 * See docs/project-management/tasks/0038-straw-and-wrap-sale-items.md.
 * The deliberate structural difference from HorseCatalogController: feed
 * products are commodity quantity goods with no field_sale_state
 * lifecycle, so this lists every published feed product rather than
 * filtering variations by sale state.
 */
class FeedCatalogController extends ControllerBase {

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
   * Builds the catalog page: one card per feed product.
   */
  public function catalog(): array {
    $product_storage = $this->entityTypeManager()->getStorage('commerce_product');
    $ids = $product_storage->getQuery()
      ->condition('type', 'feed')
      ->condition('status', TRUE)
      ->sort('title')
      ->accessCheck(TRUE)
      ->execute();

    $build = [
      '#cache' => [
        'tags' => ['commerce_product_list'],
      ],
    ];

    if (!$ids) {
      $build['empty'] = [
        '#markup' => '<p>' . $this->t('There are no feed or bedding products for sale right now — please check back soon.') . '</p>',
      ];
      return $build;
    }

    $cards = [];
    /** @var \Drupal\commerce_product\Entity\ProductInterface $product */
    foreach ($product_storage->loadMultiple($ids) as $product) {
      $cards[] = $this->buildCard($product);
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
   * Builds a single hestehoj:card render array for one feed product.
   */
  protected function buildCard(ProductInterface $product): array {
    $summary_parts = [];

    // A short plain-text teaser from the body: the summary when one was
    // written, otherwise the trimmed body text itself.
    if ($product->hasField('body') && !$product->get('body')->isEmpty()) {
      $body = $product->get('body')->first();
      $teaser = trim(strip_tags($body->summary ?: $body->value));
      if ($teaser !== '') {
        if (mb_strlen($teaser) > 120) {
          $teaser = mb_substr($teaser, 0, 119) . '…';
        }
        $summary_parts[] = $teaser;
      }
    }

    // Feed products carry one variation per harvest year (task 0038's
    // client answers), so prices can differ within a product: show the
    // cheapest, prefixed "From" when they aren't all the same.
    $prices = [];
    foreach ($product->getVariations() as $variation) {
      if ($variation->isPublished() && $variation->getPrice()) {
        $prices[] = $variation->getPrice();
      }
    }
    if ($prices) {
      usort($prices, fn ($a, $b) => $a->compareTo($b));
      $formatted = $this->currencyFormatter->format(
        $prices[0]->getNumber(),
        $prices[0]->getCurrencyCode(),
      );
      $summary_parts[] = end($prices)->equals($prices[0])
        ? $formatted
        : (string) $this->t('From @price', ['@price' => $formatted]);
    }

    $props = [
      'heading_text' => $product->label(),
      'orientation' => 'vertical',
      'style' => 'framed',
      'url' => $product->toUrl()->toString(),
      'text' => implode(' · ', $summary_parts),
    ];

    // Featured image (task 0039): first image found walking the
    // variations in order — with per-year variations the photos
    // usually sit on the current year, so don't stop at the default
    // variation.
    foreach ($product->getVariations() as $variation) {
      $media_props = shh_common_image_media_props($variation, 'field_media');
      if ($media_props) {
        $props['media'] = $media_props;
        break;
      }
    }

    return [
      '#type' => 'component',
      '#component' => 'hestehoj:card',
      '#props' => $props,
    ];
  }

}
