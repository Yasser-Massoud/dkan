<?php

namespace Drupal\datastore;

use Drupal\common\AbstractDataNodeLifeCycle;
use Drupal\common\LoggerTrait;

/**
 * DataNodeLifeCycle.
 */
class DataNodeLifeCycle extends AbstractDataNodeLifeCycle {
  use LoggerTrait;

  /**
   * Insert.
   *
   * If a CSV resource is being saved a job should be created.
   */
  public function insert() {
    $entity = $this->node;
    if ($this->getDataType() != 'distribution') {
      return;
    }

    if ($this->isDataStorable()) {
      try {
        /* @var $datastoreService \Drupal\datastore\Service */
        $datastoreService = \Drupal::service('datastore.service');
        $datastoreService->import($entity->uuid(), TRUE);
      }
      catch (\Exception $e) {
        $this->setLoggerFactory(\Drupal::service('logger.factory'));
        $this->log('datastore', $e->getMessage());
      }
    }

  }

  /**
   * Predelete.
   *
   * When a resource is deleted, any incomplete import jobs should be removed.
   * Also, its datastore should go.
   */
  public function predelete() {
    $entity = $this->node;
    if ($this->getDataType() != 'distribution') {
      return;
    }

    try {
      /* @var $datastoreService \Drupal\datastore\Service */
      $datastoreService = \Drupal::service('datastore.service');
      $datastoreService->drop($entity->uuid());
    }
    catch (\Exception $e) {
      $this->setLoggerFactory(\Drupal::service('logger.factory'));
      $this->log('datastore', $e->getMessage());
    }

    $metadata = $this->getMetaData();
    $data = $metadata->data;
    if (isset($data->downloadURL)) {
      $url = $data->downloadURL;
      $pieces = explode('sites/default/files/', $url);
      $path = "public://" . end($pieces);
      /** @var \Drupal\Core\File\FileSystemInterface $fileSystemService */
      $fileSystemService = \Drupal::service('file_system');
      $fileSystemService->delete($path);
    }
  }

  /**
   * Private.
   */
  private function isDataStorable() : bool {
    $metadata = $this->getMetaData();
    $data = $metadata->data;

    if (isset($data->downloadURL) && isset($data->mediaType)) {
      return in_array($data->mediaType, [
        'text/csv',
        'text/tab-separated-values',
      ]);
    }

    return FALSE;
  }

}
