<?php

namespace Drupal\hvg_landing;

use Drupal\Component\Uuid\Php as Uuid;
use Drupal\Core\File\FileExists;
use Drupal\Core\File\FileSystemInterface;

/**
 * Builds and saves the hivelog.eu landing page as a Canvas page.
 */
final class LandingPage {

  /**
   * Component tree items, in order.
   *
   * @var array<int, array<string, mixed>>
   */
  private array $tree = [];

  /**
   * The UUID generator.
   */
  private Uuid $uuid;

  /**
   * Creates the page unless something already lives at /home.
   *
   * @return string
   *   A status message for the deploy log.
   */
  public static function create(): string {
    $alias = \Drupal::entityTypeManager()
      ->getStorage('path_alias')
      ->loadByProperties(['alias' => '/home']);
    if ($alias) {
      return 'A page already exists at /home; left untouched.';
    }
    $builder = new self();
    $page = \Drupal::entityTypeManager()
      ->getStorage('canvas_page')
      ->create([
        'title' => 'HiveLog',
        'status' => TRUE,
        'path' => ['alias' => '/home'],
        'components' => $builder->build(),
      ]);
    $page->save();
    return 'Created the landing page at /home.';
  }

  /**
   * Builds the component tree.
   *
   * @return array<int, array<string, mixed>>
   *   Canvas component tree items.
   */
  public function build(): array {
    $this->uuid = new Uuid();
    $this->tree = [];

    $hero = $this->add('cta', [
      'heading_text' => 'Know your hives. Keep your bees.',
      'level' => 1,
      'text' => 'HiveLog is an open beekeeping logbook: apiaries, hives, queens, inspections and a seasonal duty calendar in one place. Create a free demo account in seconds, or take it into the apiary with Viculum for iOS.',
      'text_align' => 'center',
      'background_color' => 'primary',
      'overlay_opacity' => '0%',
    ]);
    $this->button('Start your demo', '/demo/start', 'primary-inverted', $hero, 'arrow-right');
    $this->button('Log in', '/user/login', 'secondary-inverted', $hero);

    $s = $this->section('100');
    $this->heading('Everything an apiary needs', $s, 'header_slot');
    $this->text('From a single backyard hive to a dozen apiaries, HiveLog keeps the records you would otherwise lose in a notebook.', $s);
    $s = $this->section('33-33-33');
    $this->card('map-pin', 'Apiaries and hives', 'Map your apiaries, track every hive and the queen that currently heads it.', $s);
    $this->card('notebook', 'Inspection logs', 'Record brood, stores, health, feeding and management actions at each visit.', $s);
    $this->card('calendar-check', 'Seasonal calendar', 'A ready-made duty calendar for varroa treatment, feeding, harvest and winter prep.', $s);
    $this->card('package', 'Inventory and costs', 'Purchases, usage tied to duties, depreciation and low-stock warnings.', $s);
    $this->card('drop', 'Harvest and income', 'Log yields for honey, wax and propolis, then see income and net position per apiary.', $s);
    $this->card('crown', 'Queen tracking', 'Provenance, breed and laying notes for each queen, with their own history.', $s);

    $s = $this->section('100', NULL, '75%');
    $this->heading('See it in action', $s, 'header_slot');
    $carousel = $this->add('hvg_landing.carousel', ['label' => 'Screenshots of HiveLog'], $s, 'main_slot');
    $this->shot('apiary-map', 'Your apiaries on the map', 'An apiary page with its location on an OpenStreetMap map and the list of hives below it.', $carousel);
    $this->shot('hive-weights', 'Hive weight over the season', 'A bar chart of a hive weighed at each inspection from May to September.', $carousel);
    $this->shot('inspections', 'Every inspection on record', 'A table of inspections showing weight, queen seen, brood, honey stores, temperament and population.', $carousel);
    $this->shot('seasonal-calendar', 'A calendar that tells you what is due', 'The seasonal calendar listing each task with its weeks, status, completion week and notes.', $carousel);

    $s = $this->section('50-50', 'muted');
    $this->heading('Web and iOS', $s, 'header_slot');
    $this->card('device-mobile', 'This demo site', 'HiveLog runs on Drupal. Explore a working installation in your browser, no install needed.', $s);
    $this->card('hexagon', 'Viculum for iOS', 'The companion app for the apiary: log inspections on the spot and sync them with HiveLog.', $s);

    $s = $this->section('50-50');
    $this->heading('Coming next', $s, 'header_slot');
    $this->card('wifi-high', 'Hosted HiveLog', 'Rent your own HiveLog and Viculum access instead of running your own server. Planned.', $s);
    $this->card('scales', 'Hive sensors', 'Weight and climate sensors that report straight into HiveLog. Planned.', $s);

    $s = $this->section('100', 'muted', '50%');
    $this->heading('Interested in hosting or sensors?', $s, 'header_slot');
    $this->text('Rental and sensor kits are not available yet. Register your interest and we will tell you when they are.', $s);
    $this->add('block.webform_block', [
      'label' => 'Register your interest',
      'label_display' => '0',
      'webform_id' => 'interest',
      'default_data' => '',
      'redirect' => FALSE,
      'lazy' => FALSE,
    ], $s, 'main_slot');
    return $this->tree;
  }

  /**
   * Appends a Beeswax component to the tree.
   *
   * @return string
   *   The new item's UUID.
   */
  private function add(string $name, array $inputs, ?string $parent = NULL, ?string $slot = NULL): string {
    $uuid = $this->uuid->generate();
    $item = [
      'uuid' => $uuid,
      'component_id' => match (TRUE) {
        str_starts_with($name, 'block.') => $name,
        str_starts_with($name, 'hvg_landing.') => "sdc.$name",
        default => "sdc.beeswax.$name",
      },
      'inputs' => $inputs,
    ];
    if ($parent) {
      $item = ['parent_uuid' => $parent, 'slot' => $slot] + $item;
    }
    $this->tree[] = $item;
    return $uuid;
  }

  /**
   * Adds a centered level-2 heading.
   */
  private function heading(string $text, string $parent, string $slot): void {
    $this->add('heading', [
      'heading_text' => $text,
      'level' => 2,
      'text_size' => 'heading-responsive-6xl',
      'text_color' => 'default',
      'align' => 'center',
    ], $parent, $slot);
  }

  /**
   * Adds a centered intro paragraph to a section's main slot.
   */
  private function text(string $text, string $parent): void {
    $this->add('text', [
      'text' => [
        'value' => "<p class=\"text-align-center\">$text</p>",
        'format' => 'canvas_html_block',
      ],
      'text_size' => 'text-lg',
      'text_color' => 'default',
    ], $parent, 'main_slot');
  }

  /**
   * Adds a section with the given grid layout.
   */
  private function section(string $columns, ?string $background = NULL, string $width = '90%'): string {
    return $this->add('section', array_filter([
      'width' => $width,
      'columns' => $columns,
      'mobile_columns' => '1',
      'margin_block_start' => '0',
      'margin_block_end' => '0',
      'padding_block_start' => '64',
      'padding_block_end' => '32',
      'background_color' => $background,
      'section_header' => TRUE,
      'section_footer' => FALSE,
    ], fn($value) => $value !== NULL));
  }

  /**
   * Adds a button to a CTA's actions slot.
   */
  private function button(string $label, string $href, string $variant, string $parent, ?string $icon = NULL): void {
    $this->add('button', array_filter([
      'variant' => $variant,
      'label' => $label,
      'href' => $href,
      'size' => 'large',
      'icon' => $icon,
      'mobile_width' => FALSE,
      'icon_first' => FALSE,
      'disabled' => FALSE,
    ], fn($value) => $value !== NULL), $parent, 'actions');
  }

  /**
   * Adds a screenshot image with a caption to a section's main slot.
   */
  private function shot(string $name, string $caption, string $alt, string $parent): void {
    $media = $this->media($name, $alt);
    $this->add('image', [
      'media' => ['target_id' => $media->id()],
      'size' => '2:1',
      'radius' => 'small',
      'caption' => $caption,
    ], $parent, 'slides');
  }

  /**
   * Returns the image media item for a bundled screenshot, creating it once.
   */
  private function media(string $name, string $alt): object {
    $etm = \Drupal::entityTypeManager();
    $existing = $etm->getStorage('media')->loadByProperties([
      'bundle' => 'image',
      'name' => "hvg-$name",
    ]);
    if ($existing) {
      return reset($existing);
    }
    $source = \Drupal::service('extension.list.module')->getPath('hvg_landing') . "/images/$name.jpg";
    $directory = 'public://hvg_landing';
    $fs = \Drupal::service('file_system');
    $fs->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY);
    $uri = $fs->copy($source, "$directory/$name.jpg", FileExists::Replace);
    $file = $etm->getStorage('file')->create(['uri' => $uri, 'status' => 1]);
    $file->save();
    $media = $etm->getStorage('media')->create([
      'bundle' => 'image',
      'name' => "hvg-$name",
      'status' => 1,
      'field_media_image' => ['target_id' => $file->id(), 'alt' => $alt],
    ]);
    $media->save();
    return $media;
  }

  /**
   * Adds an icon card to a section's main slot.
   */
  private function card(string $icon, string $title, string $description, string $parent): void {
    $this->add('card-icon', [
      'icon' => $icon,
      'icon_size' => 'large',
      'icon_align' => 'center',
      'icon_style' => 'primary_background',
      'border_radius' => 'medium',
      'background_color' => 'muted',
      'text' => $title,
      'description' => $description,
      'text_align' => 'center',
    ], $parent, 'main_slot');
  }

}
