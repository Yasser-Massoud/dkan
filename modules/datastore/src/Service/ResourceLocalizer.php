<?php

namespace Drupal\datastore\Service;

use Drupal\common\Resource;
use Drupal\common\Storage\JobStoreFactory;
use Drupal\common\Util\DrupalFiles;
use Drupal\Core\File\FileSystemInterface;
use Drupal\metastore\FileMapper;
use FileFetcher\FileFetcher;

/**
 *
 */
class ResourceLocalizer {

  const PERSPECTIVE = 'local_file';

  private $fileMapper;
  private $jobStoreFactory;
  private $drupalFiles;

  /**
   *
   */
  public function __construct(FileMapper $fileMapper, JobStoreFactory $jobStoreFactory, DrupalFiles $drupalFiles) {
    $this->fileMapper = $fileMapper;
    $this->jobStoreFactory = $jobStoreFactory;
    $this->drupalFiles = $drupalFiles;
  }

  /**
   *
   */
  public function localize(Resource $resource) {
    $this->getFileFetcher($resource);
  }

  /**
   *
   */
  public function get(Resource $resource): ?Resource {
    return $this->fileMapper->get($resource->getIdentifier(), self::PERSPECTIVE, $resource->getVersion());
  }

  /**
   *
   */
  public function getByUniqueIdentifier(string $uid) {
    [$identifier, $version] = Resource::parseUniqueIdentifier($uid);
    return $this->fileMapper->get($identifier, self::PERSPECTIVE, $version);
  }

  /**
   *
   */
  public function remove(Resource $resource) {
    /* @var $resource \Drupal\common\Resource */
    $resource = $this->fileMapper->get($resource->getIdentifier(), self::PERSPECTIVE, $resource->getVersion());
    if ($resource) {
      $this->fileMapper->remove($resource);
      if (file_exists($resource->getFilePath())) {
        unlink($resource->getFilePath());
      }
      $this->getJobStoreFactory()->getInstance(FileFetcher::class)->remove($resource->getUniqueIdentifier());
    }
  }

  /**
   *
   */
  public function getFileFetcher(Resource $resource): FileFetcher {
    $uuid = "{$resource->getIdentifier()}_{$resource->getVersion()}";
    $directory = "file://{$this->drupalFiles->getPublicFilesDirectory()}/resources/{$uuid}";
    $this->drupalFiles->getFilesystem()->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY);
    $config = [
      'filePath' => $resource->getFilePath(),
      'temporaryDirectory' => $directory,
    ];
    return FileFetcher::get($uuid, $this->getJobStoreFactory()->getInstance(FileFetcher::class), $config);
  }

  /**
   *
   */
  public function getFileMapper(): FileMapper {
    return $this->fileMapper;
  }

  /**
   *
   */
  private function getJobStoreFactory() {
    return $this->jobStoreFactory;
  }

}
