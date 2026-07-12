<?php

namespace Drupal\shh_data_retention\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\State\StateInterface;
use Drupal\shh_data_retention\RetentionManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Read-only status report for the retention purges (task 0006).
 *
 * Deliberately no settings form: retention windows are a policy
 * decision that must land together with the published privacy policy
 * text, so they are changed in config and exported (decision 0020),
 * not toggled ad hoc in a UI.
 */
class RetentionStatusController extends ControllerBase {

  public function __construct(
    protected RetentionManager $retentionManager,
    protected StateInterface $state,
    protected DateFormatterInterface $dateFormatter,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('shh_data_retention.manager'),
      $container->get('state'),
      $container->get('date.formatter'),
    );
  }

  /**
   * Builds the status table.
   */
  public function status(): array {
    $runs = $this->state->get(RetentionManager::STATE_KEY, []);

    $rows = [];
    foreach ($this->retentionManager->categories() as $category => $info) {
      $days = $this->retentionManager->window($category);
      $eligible = $this->retentionManager->eligibleCount($category);
      $run = $runs[$category] ?? NULL;
      $rows[] = [
        $info['label'],
        $days === NULL
          ? $this->t('No window set — purge disabled')
          : $this->formatPlural($days, '1 day', '@count days'),
        $info['anchor'],
        $eligible ?? '—',
        $run
          ? $this->t('@time (@count deleted)', [
            '@time' => $this->dateFormatter->format($run['time'], 'short'),
            '@count' => $run['deleted'],
          ])
          : $this->t('Never'),
      ];
    }

    $retained_rows = [];
    foreach ($this->retentionManager->retainedByDesign() as $retained) {
      $retained_rows[] = [$retained['label'], $retained['reason']];
    }

    return [
      'intro' => [
        '#markup' => '<p>' . $this->t('Retention windows come from <code>shh_data_retention.settings</code>; a category with no window is not purged and says so. The published privacy policy and this configuration must always match (task 0006).') . '</p>',
      ],
      'table' => [
        '#type' => 'table',
        '#header' => [
          $this->t('Category'),
          $this->t('Window'),
          $this->t('Anchored to'),
          $this->t('Eligible now'),
          $this->t('Last purge run'),
        ],
        '#rows' => $rows,
        '#empty' => $this->t('No categories defined.'),
        '#caption' => $this->t('Purged automatically (daily cron)'),
      ],
      'retained' => [
        '#type' => 'table',
        '#header' => [$this->t('Data'), $this->t('Why it is kept')],
        '#rows' => $retained_rows,
        '#caption' => $this->t('Kept with no automatic purge — deliberate decisions (task 0047), not pending items'),
      ],
      '#cache' => ['max-age' => 0],
    ];
  }

}
