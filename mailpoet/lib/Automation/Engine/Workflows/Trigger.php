<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Workflows;

use MailPoet\Automation\Engine\Data\StepRunArgs;

interface Trigger extends Step {
  public function registerHooks(): void;

  public function isTriggeredBy(StepRunArgs $args): bool;
}
