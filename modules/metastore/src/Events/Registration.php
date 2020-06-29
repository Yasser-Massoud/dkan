<?php
namespace Drupal\metastore\Events;

use Symfony\Component\EventDispatcher\Event;

class Registration extends Event {
  private $data;

  public function __construct($data) {
    $this->data = $data;
  }

  public function getData() {
    return $this->data;
  }
}
