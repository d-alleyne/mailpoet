<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Control;

use Exception;
use MailPoet\Automation\Engine\Control\Steps\ActionStepRunner;
use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Data\StepRunArgs;
use MailPoet\Automation\Engine\Data\SubjectEntry;
use MailPoet\Automation\Engine\Data\WorkflowRun;
use MailPoet\Automation\Engine\Data\WorkflowRunLog;
use MailPoet\Automation\Engine\Exceptions;
use MailPoet\Automation\Engine\Exceptions\InvalidStateException;
use MailPoet\Automation\Engine\Hooks;
use MailPoet\Automation\Engine\Storage\WorkflowRunLogStorage;
use MailPoet\Automation\Engine\Storage\WorkflowRunStorage;
use MailPoet\Automation\Engine\Storage\WorkflowStorage;
use MailPoet\Automation\Engine\WordPress;
use MailPoet\Automation\Engine\Workflows\Action;
use MailPoet\Automation\Engine\Workflows\Payload;
use MailPoet\Automation\Engine\Workflows\Subject;
use Throwable;

class StepHandler {
  /** @var ActionScheduler */
  private $actionScheduler;

  /** @var ActionStepRunner */
  private $actionStepRunner;

  /** @var SubjectLoader */
  private $subjectLoader;

  /** @var WordPress */
  private $wordPress;

  /** @var WorkflowRunStorage */
  private $workflowRunStorage;

  /** @var WorkflowStorage */
  private $workflowStorage;

  /** @var array<string, StepRunner> */
  private $stepRunners;

  /** @var WorkflowRunLogStorage */
  private $workflowRunLogStorage;

  /** @var Hooks */
  private $hooks;

  public function __construct(
    ActionScheduler $actionScheduler,
    ActionStepRunner $actionStepRunner,
    Hooks $hooks,
    SubjectLoader $subjectLoader,
    WordPress $wordPress,
    WorkflowRunStorage $workflowRunStorage,
    WorkflowRunLogStorage $workflowRunLogStorage,
    WorkflowStorage $workflowStorage
  ) {
    $this->actionScheduler = $actionScheduler;
    $this->actionStepRunner = $actionStepRunner;
    $this->hooks = $hooks;
    $this->subjectLoader = $subjectLoader;
    $this->wordPress = $wordPress;
    $this->workflowRunStorage = $workflowRunStorage;
    $this->workflowRunLogStorage = $workflowRunLogStorage;
    $this->workflowStorage = $workflowStorage;
  }

  public function initialize(): void {
    $this->wordPress->addAction(Hooks::WORKFLOW_STEP, [$this, 'handle']);
    $this->addStepRunner(Step::TYPE_ACTION, $this->actionStepRunner);
    $this->wordPress->doAction(Hooks::STEP_RUNNER_INITIALIZE, [$this]);
  }

  public function addStepRunner(string $stepType, StepRunner $stepRunner): void {
    $this->stepRunners[$stepType] = $stepRunner;
  }

  /** @param mixed $args */
  public function handle($args): void {
    // TODO: args validation
    if (!is_array($args)) {
      throw new InvalidStateException();
    }

    // Action Scheduler catches only Exception instances, not other errors.
    // We need to convert them to exceptions to be processed and logged.
    try {
      $this->handleStep($args);
    } catch (Throwable $e) {
      $this->workflowRunStorage->updateStatus((int)$args['workflow_run_id'], WorkflowRun::STATUS_FAILED);
      if (!$e instanceof Exception) {
        throw new Exception($e->getMessage(), intval($e->getCode()), $e);
      }
      throw $e;
    }
  }

  private function handleStep(array $args): void {
    $workflowRunId = $args['workflow_run_id'];
    $stepId = $args['step_id'];

    $workflowRun = $this->workflowRunStorage->getWorkflowRun($workflowRunId);
    if (!$workflowRun) {
      throw Exceptions::workflowRunNotFound($workflowRunId);
    }

    if ($workflowRun->getStatus() !== WorkflowRun::STATUS_RUNNING) {
      throw Exceptions::workflowRunNotRunning($workflowRunId, $workflowRun->getStatus());
    }

    $workflow = $this->workflowStorage->getWorkflow($workflowRun->getWorkflowId(), $workflowRun->getVersionId());
    if (!$workflow) {
      throw Exceptions::workflowVersionNotFound($workflowRun->getWorkflowId(), $workflowRun->getVersionId());
    }

    // complete workflow run
    if (!$stepId) {
      $this->workflowRunStorage->updateStatus($workflowRunId, WorkflowRun::STATUS_COMPLETE);
      return;
    }

    $step = $workflow->getStep($stepId);
    if (!$step) {
      throw Exceptions::workflowStepNotFound($stepId);
    }

    $stepType = $step->getType();
    if (isset($this->stepRunners[$stepType])) {
      $log = new WorkflowRunLog($workflowRun->getId(), $step->getId());
      try {
        $requiredSubjects = $step instanceof Action ? $step->getSubjectKeys() : [];
        $subjectEntries = $this->getSubjectEntries($workflowRun, $requiredSubjects);
        $args = new StepRunArgs($workflow, $workflowRun, $step, $subjectEntries);
        $this->stepRunners[$stepType]->run($args);
        $log->markCompletedSuccessfully();
      } catch (Throwable $e) {
        $log->markFailed();
        $log->setError($e);
        throw $e;
      } finally {
        try {
          $this->hooks->doWorkflowStepAfterRun($log);
        } catch (Throwable $e) {
          // Ignore integration errors
        }
        $this->workflowRunLogStorage->createWorkflowRunLog($log);
      }
    } else {
      throw new InvalidStateException();
    }

    $nextStep = $step->getNextSteps()[0] ?? null;
    $nextStepArgs = [
      [
        'workflow_run_id' => $workflowRunId,
        'step_id' => $nextStep ? $nextStep->getId() : null,
      ],
    ];

    // next step scheduled by action
    if ($this->actionScheduler->hasScheduledAction(Hooks::WORKFLOW_STEP, $nextStepArgs)) {
      return;
    }

    // no need to schedule a new step if the next step is null, complete the run
    if (!$nextStep) {
      $this->workflowRunStorage->updateStatus($workflowRunId, WorkflowRun::STATUS_COMPLETE);
      return;
    }

    // enqueue next step
    $this->actionScheduler->enqueue(Hooks::WORKFLOW_STEP, $nextStepArgs);
    // TODO: allow long-running steps (that are not done here yet)
  }

  /** @return SubjectEntry<Subject<Payload>>[] */
  private function getSubjectEntries(WorkflowRun $workflowRun, array $requiredSubjectKeys): array {
    $subjectDataMap = [];
    foreach ($workflowRun->getSubjects() as $data) {
      $subjectDataMap[$data->getKey()] = array_merge($subjectDataMap[$data->getKey()] ?? [], [$data]);
    }

    $subjectEntries = [];
    foreach ($requiredSubjectKeys as $key) {
      $subjectData = $subjectDataMap[$key] ?? null;
      if (!$subjectData) {
        throw Exceptions::subjectDataNotFound($key, $workflowRun->getId());
      }
      foreach ($subjectData as $data) {
        $subjectEntries[] = $this->subjectLoader->getSubjectEntry($data);
      }
    }
    return $subjectEntries;
  }
}
