<?php

namespace Drupal\shh_reporting\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\shh_reporting\ReportBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Staff reports: facility utilization and revenue (task 0008).
 *
 * Ranges come from `from`/`to` query parameters (Y-m-d, `to`
 * inclusive) with sensible defaults and preset links — deliberately no
 * FAPI filter form; the presets cover the recurring uses and the
 * parameters cover the rest, and every view has a CSV export link
 * carrying the same range.
 */
class ReportingController extends ControllerBase {

  public function __construct(
    protected ReportBuilder $builder,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('shh_reporting.builder'),
    );
  }

  /**
   * Facility utilization per period.
   */
  public function utilization(Request $request): array {
    [$from, $to_exclusive, $to] = $this->range($request, '-8 weeks');
    $granularity = $request->query->get('granularity') === 'month' ? 'month' : 'week';
    $rows = $this->builder->utilization($from, $to_exclusive, $granularity);

    $table_rows = [];
    foreach ($rows as $row) {
      $table_rows[] = [
        $row['period'],
        $row['facility'],
        number_format($row['open_hours'], 1),
        number_format($row['booked_hours'], 1),
        number_format($row['blocked_hours'], 1),
        number_format($row['utilization'] * 100, 1) . ' %',
      ];
    }

    $build = $this->presets('shh_reporting.utilization', [
      (string) $this->t('Last 8 weeks (weekly)') => ['granularity' => 'week'],
      (string) $this->t('Last 6 months (monthly)') => [
        'from' => (new \DateTimeImmutable('first day of this month'))->modify('-5 months')->format('Y-m-d'),
        'granularity' => 'month',
      ],
      (string) $this->t('Year to date (monthly)') => [
        'from' => (new \DateTimeImmutable('first day of january this year'))->format('Y-m-d'),
        'granularity' => 'month',
      ],
    ]);
    $build['table'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Period'),
        $this->t('Facility'),
        $this->t('Open hours'),
        $this->t('Booked hours'),
        $this->t('Blocked hours (staff)'),
        $this->t('Utilization'),
      ],
      '#rows' => $table_rows,
      '#empty' => $this->t('No facilities or no data in this range.'),
      '#caption' => $this->t('@from – @to · utilization = booked ÷ open (08:00–20:00 per day, per unit); staff blocks reported separately', [
        '@from' => $from->format('Y-m-d'),
        '@to' => $to->format('Y-m-d'),
      ]),
    ];
    $build['export'] = $this->exportLink('shh_reporting.utilization_csv', [
      'from' => $from->format('Y-m-d'),
      'to' => $to->format('Y-m-d'),
      'granularity' => $granularity,
    ]);
    $build['#cache']['max-age'] = 0;
    return $build;
  }

  /**
   * Utilization CSV export.
   */
  public function utilizationCsv(Request $request): Response {
    [$from, $to_exclusive, $to] = $this->range($request, '-8 weeks');
    $granularity = $request->query->get('granularity') === 'month' ? 'month' : 'week';
    $lines = [['period', 'facility', 'open_hours', 'booked_hours', 'blocked_hours', 'utilization_pct']];
    foreach ($this->builder->utilization($from, $to_exclusive, $granularity) as $row) {
      $lines[] = [
        $row['period'],
        $row['facility'],
        round($row['open_hours'], 2),
        round($row['booked_hours'], 2),
        round($row['blocked_hours'], 2),
        round($row['utilization'] * 100, 2),
      ];
    }
    return $this->csvResponse($lines, 'facility-utilization-' . $from->format('Ymd') . '-' . $to->format('Ymd') . '.csv');
  }

  /**
   * Revenue by order item type.
   */
  public function revenue(Request $request): array {
    [$from, $to_exclusive, $to] = $this->range($request, '-30 days');
    $data = $this->builder->revenue($from, $to_exclusive);

    $table_rows = [];
    foreach ($data['items'] as $row) {
      $table_rows[] = [
        $row['label'],
        $row['order_count'],
        $row['item_count'],
        number_format((float) $row['gross'], 2) . ' DKK',
      ];
    }
    foreach ($data['adjustments'] as $label => $sum) {
      $table_rows[] = [
        $this->t('Order adjustment: @label', ['@label' => $label]),
        '',
        '',
        number_format((float) $sum, 2) . ' DKK',
      ];
    }
    $table_rows[] = [
      ['data' => $this->t('Order totals (@count orders)', ['@count' => $data['order_count']]), 'header' => TRUE],
      '',
      '',
      ['data' => number_format((float) $data['orders_total'], 2) . ' DKK', 'header' => TRUE],
    ];

    $build = $this->presets('shh_reporting.revenue', [
      (string) $this->t('Last 30 days') => [],
      (string) $this->t('This month') => ['from' => (new \DateTimeImmutable('first day of this month'))->format('Y-m-d')],
      (string) $this->t('Last month') => [
        'from' => (new \DateTimeImmutable('first day of last month'))->format('Y-m-d'),
        'to' => (new \DateTimeImmutable('last day of last month'))->format('Y-m-d'),
      ],
      (string) $this->t('Year to date') => [
        'from' => (new \DateTimeImmutable('first day of january this year'))->format('Y-m-d'),
      ],
    ]);
    $build['table'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Order item type'),
        $this->t('Orders'),
        $this->t('Items'),
        $this->t('Gross (VAT incl.)'),
      ],
      '#rows' => $table_rows,
      '#empty' => $this->t('No placed orders in this range.'),
      '#caption' => $this->t('@from – @to · placed, non-canceled orders; item lines are gross before order-level adjustments, which are listed separately so the total reconciles', [
        '@from' => $from->format('Y-m-d'),
        '@to' => $to->format('Y-m-d'),
      ]),
    ];
    $build['export'] = $this->exportLink('shh_reporting.revenue_csv', [
      'from' => $from->format('Y-m-d'),
      'to' => $to->format('Y-m-d'),
    ]);
    $build['#cache']['max-age'] = 0;
    return $build;
  }

  /**
   * Revenue CSV export.
   */
  public function revenueCsv(Request $request): Response {
    [$from, $to_exclusive, $to] = $this->range($request, '-30 days');
    $data = $this->builder->revenue($from, $to_exclusive);
    $lines = [['line', 'orders', 'items', 'gross_dkk']];
    foreach ($data['items'] as $row) {
      $lines[] = [$row['label'], $row['order_count'], $row['item_count'], $row['gross']];
    }
    foreach ($data['adjustments'] as $label => $sum) {
      $lines[] = ["adjustment: $label", '', '', $sum];
    }
    $lines[] = ['order_totals', $data['order_count'], '', $data['orders_total']];
    return $this->csvResponse($lines, 'revenue-' . $from->format('Ymd') . '-' . $to->format('Ymd') . '.csv');
  }

  /**
   * Parses from/to query params (Y-m-d, `to` inclusive).
   *
   * @return array
   *   [from midnight, to-exclusive midnight, to (inclusive date)].
   */
  protected function range(Request $request, string $default_from): array {
    try {
      $to = new \DateTimeImmutable($request->query->get('to', 'today'));
      $from = new \DateTimeImmutable($request->query->get('from', $to->modify($default_from)->format('Y-m-d')));
    }
    catch (\Exception) {
      throw new BadRequestHttpException('Invalid from/to date.');
    }
    $from = $from->setTime(0, 0);
    $to = $to->setTime(0, 0);
    if ($to < $from) {
      throw new BadRequestHttpException('to precedes from.');
    }
    return [$from, $to->modify('+1 day'), $to];
  }

  /**
   * Preset range links for a report route.
   */
  protected function presets(string $route, array $presets): array {
    $links = [];
    foreach ($presets as $label => $query) {
      $links[] = [
        '#type' => 'link',
        '#title' => $label,
        '#url' => Url::fromRoute($route, [], ['query' => $query]),
        '#attributes' => ['class' => ['button']],
      ];
    }
    return [
      'presets' => [
        '#type' => 'container',
        'links' => $links,
      ],
    ];
  }

  /**
   * A "Download CSV" link for an export route.
   */
  protected function exportLink(string $route, array $query): array {
    return [
      '#type' => 'link',
      '#title' => $this->t('Download CSV'),
      '#url' => Url::fromRoute($route, [], ['query' => $query]),
      '#attributes' => ['class' => ['button', 'button--primary']],
      '#prefix' => '<p>',
      '#suffix' => '</p>',
    ];
  }

  /**
   * Builds a CSV download response.
   */
  protected function csvResponse(array $lines, string $filename): Response {
    $handle = fopen('php://temp', 'r+');
    foreach ($lines as $line) {
      fputcsv($handle, $line, ',', '"', '\\');
    }
    rewind($handle);
    $csv = stream_get_contents($handle);
    fclose($handle);

    $response = new Response($csv);
    $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
    $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');
    $response->setPrivate();
    return $response;
  }

}
