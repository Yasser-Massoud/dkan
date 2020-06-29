<?php

namespace Drupal\datastore\EventSubscriber;

use Drupal\metastore\Events\Registration;
use Drupal\metastore\FileMapper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class Subscriber implements EventSubscriberInterface {

  public static function getSubscribedEvents() {
    $events = [];
    $events[FileMapper::EVENT_REGISTRATION][] = ['onRegistration'];
    return $events;
  }

  public function onRegistration(Registration $event) {
    /* @var $logger \Drupal\Core\Logger\LoggerChannel */
    $logger = \Drupal::service('logger.channel.default');
    $logger->notice("Event on Registration was triggered.");
  }

}
