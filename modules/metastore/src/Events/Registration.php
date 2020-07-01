<?php

namespace Drupal\metastore\Events;

use Drupal\common\Resource;
use Symfony\Component\EventDispatcher\Event;

/**
 *
 */
class Registration extends Event {
  private $resource;

  /**
   *
   */
  public function __construct(Resource $resource) {
    $this->resource = $resource;
  }

  /**
   *
   */
  public function getResource(): Resource {
    return $this->resource;
  }

}
