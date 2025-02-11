<?php

namespace MailPoet\Config;

use WP_Upgrader_Skin;

/**
 * Upgrader skin that doesn't echo any outputs
 * Used for when downloading translation updates in the background
 */
class SilentUpgraderSkin extends WP_Upgrader_Skin {
  /**
   * @inerhitDoc
   */
  public function feedback($feedback, ...$args) {
  }

  /**
   * @inerhitDoc
   */
  public function header() {
  }

  /**
   * @inerhitDoc
   */
  public function footer() {
  }
}
