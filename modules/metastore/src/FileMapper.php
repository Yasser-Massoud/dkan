<?php

namespace Drupal\metastore;

use Drupal\Core\File\FileSystemInterface;
use Drupal\common\Storage\JobStoreFactory;
use Drupal\common\Util\DrupalFiles;
use FileFetcher\FileFetcher;
use FileFetcher\Processor\Remote;
use Procrastinator\Result;

/**
 * FileMapper.
 */
class FileMapper {

  /**
   * @var \Contracts\StorerInterface | \Contracts\RetrieverInterface
   */
  private $store;

  /**
   * Constructor.
   */
  public function __construct($store) {
    $this->store = $store;
  }

  /**
   * Register a new url for mapping.
   */
  public function register(string $url) : array {
    $uuid = md5($url);

    if (!$this->exists($uuid)) {
      /*$directory = $this->getLocalDirectory($uuid);
      $this->drupalFiles->getFilesystem()
        ->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY);

      $this->getFileFetcher($uuid, $url);*/

      $this->store->store($url, $uuid);
      return [$uuid, '12345'];
    }
    throw new \Exception("URL already registered.");
  }


  /**
   * Retrieve the originally registered URL.
   */
  public function get(string $uuid, $type = 'source', $revision = null) {
    return $this->store->retrieve($uuid);
  }

  /**
   * Private.
   */
  private function exists($uuid) {
    $instance = $this->store->retrieve($uuid);
    return isset($instance);
  }

  /**
   * Get the Drupal URL for a local instance of a registered URL.
   */
  /*public function getLocalUrl(string $uuid) : ?string {
    if ($this->exists($uuid)) {
      $ourselves = $this->getFileFetcher($uuid);
      if ($ourselves->getResult()->getStatus() == Result::DONE) {
        $localFilePath = $ourselves->getStateProperty("destination");
        $publicSchemed = str_replace($this->drupalFiles->getPublicFilesDirectory(), "public://", $localFilePath);
        return $this->drupalFiles->fileCreateUrl($publicSchemed);
      }
    }
    throw new \Exception("Unknown URL.");
  }*/

  /**
   * Getter.
   */
  /*public function getFileFetcher($uuid, $url = '') {
    $fileFetcherConfig = [
      'filePath' => $url,
      'processors' => $this->fileFetcherProcessors,
      'temporaryDirectory' => $this->getLocalDirectory($uuid),
    ];

    return FileFetcher::get($uuid, $this->jobStore, $fileFetcherConfig);
  }*/

  /**
   * Private.
   */
  /*private function getLocalDirectory($uuid) {
    $publicPath = $this->drupalFiles->getPublicFilesDirectory();
    return $publicPath . '/resources/' . $uuid;
  }*/

}
