<?php

namespace Drupal\metastore\Traits;

use Drupal\metastore\FileMapper;

/**
 *
 */
trait FileMapperTrait {
  private $fileMapper;

  /**
   * Setter.
   */
  public function setFileMapper(FileMapper $fileMapper) {
    $this->fileMapper = $fileMapper;
  }

  /**
   * Getter.
   */
  private function getFileMapper(): FileMapper {
    if (!isset($this->fileMapper)) {
      throw new \Exception("FileMapper not set.");
    }
    return $this->fileMapper;
  }

}
