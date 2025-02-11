<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Builder;

use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Data\Workflow;
use MailPoet\Automation\Engine\Exceptions;
use MailPoet\Automation\Engine\Registry;

class UpdateStepsController {
  /** @var Registry */
  private $registry;

  public function __construct(
    Registry $registry
  ) {
    $this->registry = $registry;
  }

  public function updateSteps(Workflow $workflow, array $data): Workflow {
    $steps = [];
    foreach ($data as $index => $stepData) {
      $step = $this->processStep($stepData, $workflow->getStep($stepData['id']));
      $steps[$index] = $step;
    }
    $workflow->setSteps($steps);
    return $workflow;
  }

  private function processStep(array $data, ?Step $existingStep): Step {
    $key = $data['key'];
    $step = $this->registry->getStep($key);
    if (!$step && $existingStep && $data !== $existingStep->toArray()) {
      throw Exceptions::workflowStepNotFound($key);
    }
    return Step::fromArray($data);
  }
}
