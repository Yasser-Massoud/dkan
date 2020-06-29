<?php

namespace Drupal\datastore\LifeCycle;

use Drupal\metastore\LifeCycle\AbstractData;
use Drupal\common\LoggerTrait;

/**
 * Data.
 */
class Data extends AbstractData {
  use LoggerTrait;

  /**
   * Predelete.
   *
   * When a resource is deleted, any incomplete import jobs should be removed.
   * Also, its datastore should go.
   */
  public function predelete() {
    if ($this->data->getDataType() != 'distribution') {
      return;
    }

    try {
      /* @var $datastoreService \Drupal\datastore\Service */
      $datastoreService = \Drupal::service('datastore.service');
      $datastoreService->drop($this->data->getIdentifier());
    }
    catch (\Exception $e) {
      $this->setLoggerFactory(\Drupal::service('logger.factory'));
      $this->log('datastore', $e->getMessage());
    }

    $metadata = $this->data->getMetaData();
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

}
