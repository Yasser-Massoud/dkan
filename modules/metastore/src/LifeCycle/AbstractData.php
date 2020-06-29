<?php

namespace Drupal\metastore\LifeCycle;

use Drupal\metastore\NodeWrapper\Data as Wrapper;

abstract class AbstractData {

  protected $data;

  public function __construct(Wrapper $data) {
    $this->data = $data;
  }

  protected function go($stage) {
    $method = "{$this->data->getDataType()}{$stage}";
    if (method_exists($this, $method)) {
      $this->{$method}();
    }
  }

}
