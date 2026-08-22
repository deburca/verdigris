<?php

namespace Drupal\shh_feed_catalog;

use CommerceGuys\Intl\Formatter\CurrencyFormatterInterface;
use Drupal\commerce_product\Entity\ProductInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Finds the feed products and builds their cards.
 *
 * Factored out of FeedCatalogController (task 0051 section 5) so the
 * homepage's feed section renders the same card, from the same query, as
 * /feed — the pattern established by HorseCardBuilder (section 3) and
 * FacilityCardBuilder (section 4).
 *
 * Live data matters more here than anywhere: task 0038's stock decision
 * is that **publish/unpublish is the only availability lever**, per
 * product *or per harvest-year variation*. A hardcoded homepage teaser
 * would keep advertising a year of wrap that staff unpublished the day
 * it ran out.
 */
class FeedCardBuilder {

  use StringTranslationTrait;

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected CurrencyFormatterInterface $currencyFormatter,
  ) {}

  /**
   * The published feed products, by title.
   *
   * @return \Drupal\commerce_product\Entity\ProductInterface[]
   *   Published products of the `feed` type.
   */
  public function feedProducts(): array {
    $storage = $this->entityTypeManager->getStorage('commerce_product');
    $ids = $storage->getQuery()
      ->condition('type', 'feed')
      ->condition('status', TRUE)
      ->sort('title')
      ->accessCheck(TRUE)
      ->execute();
    return $ids ? $storage->loadMultiple($ids) : [];
  }

  /**
   * A single hestehoj:card render array for one feed product.
   */
  public function buildCard(ProductInterface $product): array {
    $summary_parts = [];

    // A short plain-text teaser: the body's summary when one was
    // written, otherwise the trimmed body itself.
    if ($product->hasField('body') && !$product->get('body')->isEmpty()) {
      $body = $product->get('body')->first();
      $teaser = trim(preg_replace('/\s+/', ' ', strip_tags($body->summary ?: $body->value)));
      if ($teaser !== '') {
        if (mb_strlen($teaser) > 120) {
          $teaser = mb_substr($teaser, 0, 119) . '…';
        }
        $summary_parts[] = $teaser;
      }
    }

    // Prices differ per harvest year (task 0038), so show the cheapest
    // published one, prefixed "From" when they are not all the same —
    // and always say the unit, since the client confirmed prices are per
    // bale and a bare figure invites the wrong guess.
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
        ? (string) $this->t('@price per bale', ['@price' => $formatted])
        : (string) $this->t('From @price per bale', ['@price' => $formatted]);
    }

    $props = [
      'heading_text' => $product->label(),
      'orientation' => 'vertical',
      'style' => 'framed',
      'url' => $product->toUrl()->toString(),
      'text' => implode(' · ', $summary_parts),
    ];

    // Featured image: the first image found walking the variations —
    // photos usually sit on the current year, not necessarily the
    // default variation (task 0039).
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
