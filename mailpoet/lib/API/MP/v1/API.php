<?php

namespace MailPoet\API\MP\v1;

use MailPoet\Config\Changelog;

/**
 * API used by other plugins
 * Do not add bodies of methods into this class. Use other classes. See CustomFields or Subscribers.
 * This class is under refactor, and we are going to move most of the remaining implementations from here.
 */
class API {
  /** @var CustomFields */
  private $customFields;

  /** @var Segments */
  private $segments;

  /** @var Subscribers */
  private $subscribers;

  /** @var Changelog */
  private $changelog;

  public function __construct(
    CustomFields $customFields,
    Segments $segments,
    Subscribers $subscribers,
    Changelog $changelog
  ) {
    $this->customFields = $customFields;
    $this->segments = $segments;
    $this->subscribers = $subscribers;
    $this->changelog = $changelog;
  }

  public function getSubscriberFields() {
    return $this->customFields->getSubscriberFields();
  }

  public function addSubscriberField(array $data = []) {
    try {
      return $this->customFields->addSubscriberField($data);
    } catch (\InvalidArgumentException $e) {
      throw new APIException($e->getMessage(), $e->getCode(), $e);
    }
  }

  /**
   * @throws APIException
   */
  public function subscribeToList($subscriberId, $listId, $options = []): array {
    return $this->subscribeToLists($subscriberId, [$listId], $options);
  }

  /**
   * @throws APIException
   */
  public function subscribeToLists($subscriberId, array $listIds, $options = []) {
      return $this->subscribers->subscribeToLists($subscriberId, $listIds, $options);
  }

  public function unsubscribeFromList($subscriberId, $listId) {
    return $this->unsubscribeFromLists($subscriberId, [$listId]);
  }

  public function unsubscribeFromLists($subscriberId, array $listIds) {
    return $this->subscribers->unsubscribeFromLists($subscriberId, $listIds);
  }

  public function getLists(): array {
    return $this->segments->getAll();
  }

  public function addSubscriber(array $subscriber, $listIds = [], $options = []): array {
    return $this->subscribers->addSubscriber($subscriber, $listIds, $options);
  }

  public function addList(array $list) {
    return $this->segments->addList($list);
  }

  public function getSubscriber($subscriberEmail) {
    return $this->subscribers->getSubscriber($subscriberEmail);
  }

  public function isSetupComplete() {
    return !(
      $this->changelog->shouldShowWelcomeWizard()
      || $this->changelog->shouldShowWooCommerceListImportPage()
      || $this->changelog->shouldShowRevenueTrackingPermissionPage()
    );
  }
}
