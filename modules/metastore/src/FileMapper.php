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

  private $jobStore;
  private $drupalFiles;

  private $fileFetcherProcessors;

  /**
   * Constructor.
   */
  public function __construct(JobStoreFactory $jobStoreFactory, DrupalFiles $drupalFiles) {
    $this->jobStore = $jobStoreFactory->getInstance(FileFetcher::class);
    $this->drupalFiles = $drupalFiles;
    $this->fileFetcherProcessors = [
      Remote::class,
    ];
  }

  /**
   * Setter.
   */
  public function setFileFetcherProcessors(array $fileFetcherProcessors) {
    $this->fileFetcherProcessors = $fileFetcherProcessors;
  }

  /**
   * Register a new url for mapping.
   */
  public function register(string $url) : string {
    $uuid = md5($url);

    if (!$this->exists($uuid)) {
      /*$directory = $this->getLocalDirectory($uuid);
      $this->drupalFiles->getFilesystem()
        ->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY);

      $this->getFileFetcher($uuid, $url);*/

      return $uuid;
    }
    throw new \Exception("URL already registered.");
  }

  /**
   * Retrieve the originally registered URL.
   */
  public function getSource(string $uuid) {
    $ff = $this->getFileFetcher($uuid);
    return $ff->getStateProperty('source');
  }

  /**
   * Get the Drupal URL for a local instance of a registered URL.
   */
  public function getLocalUrl(string $uuid) : ?string {
    if ($this->exists($uuid)) {
      $ourselves = $this->getFileFetcher($uuid);
      if ($ourselves->getResult()->getStatus() == Result::DONE) {
        $localFilePath = $ourselves->getStateProperty("destination");
        $publicSchemed = str_replace($this->drupalFiles->getPublicFilesDirectory(), "public://", $localFilePath);
        return $this->drupalFiles->fileCreateUrl($publicSchemed);
      }
    }
    throw new \Exception("Unknown URL.");
  }

  /**
   * Getter.
   */
  public function getFileFetcher($uuid, $url = '') {
    $fileFetcherConfig = [
      'filePath' => $url,
      'processors' => $this->fileFetcherProcessors,
      'temporaryDirectory' => $this->getLocalDirectory($uuid),
    ];

    return FileFetcher::get($uuid, $this->jobStore, $fileFetcherConfig);
  }

  /**
   * Private.
   */
  private function exists($uuid) {
    $instance = $this->jobStore->retrieve($uuid);
    return isset($instance);
  }

  /**
   * Private.
   */
  private function getLocalDirectory($uuid) {
    $publicPath = $this->drupalFiles->getPublicFilesDirectory();
    return $publicPath . '/resources/' . $uuid;
  }

}
