<?php

namespace Drupal\hvg_demo\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Session\AccountInterface;

/**
 * Hides the "Try the demo" buttons from people who are signed in.
 *
 * The buttons are Canvas components (header region and landing page) with no
 * per-visitor visibility, and /demo/start answers access denied to a signed-in
 * user, so a stylesheet attached only to signed-in pages hides them.
 */
final class DemoButtons {

  /**
   * Constructs the hook class.
   */
  public function __construct(private readonly AccountInterface $currentUser) {}

  /**
   * Implements hook_page_attachments().
   */
  #[Hook('page_attachments')]
  public function pageAttachments(array &$attachments): void {
    $attachments['#cache']['contexts'][] = 'user.roles:authenticated';
    if ($this->currentUser->isAuthenticated()) {
      $attachments['#attached']['library'][] = 'hvg_demo/signed_in';
    }
  }

}
