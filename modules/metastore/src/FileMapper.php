<?php

namespace Drupal\metastore;

use Drupal\common\Resource;
use Drupal\common\Storage\DatabaseTableInterface;
use Drupal\common\Storage\Query;
use Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher;
use Drupal\metastore\Events\Registration;

/**
 * FileMapper.
 */
class FileMapper {

  const EVENT_REGISTRATION = 'dkan_metastore_filemapper_register';

  private $store;
  private $eventDispatcher;

  /**
   * Constructor.
   */
  public function __construct(DatabaseTableInterface $store, ContainerAwareEventDispatcher $eventDispatcher) {
    $this->store = $store;
    $this->eventDispatcher = $eventDispatcher;
  }

  /**
   * Register a new url for mapping.
   *
   * @todo the Resource class currently lives in datastore, we should move it
   * to a more neutral place.
   */
  public function register(Resource $resource) : bool {
    if (!$this->filePathExists($resource->getFilePath())) {
      $this->store->store(json_encode($resource), $resource->getUniqueIdentifier());
      $this->eventDispatcher->dispatch(self::EVENT_REGISTRATION, new Registration($resource));

      return TRUE;
    }
    throw new \Exception("URL already registered.");
  }

  /**
   *
   */
  public function registerNewPerspective(Resource $resource) {
    $identifier = $resource->getIdentifier();
    $version = $resource->getVersion();
    $perspective = $resource->getPerspective();
    if ($this->exists($identifier, 'source', $version)) {
      if (!$this->exists($identifier, $perspective, $version)) {
        $this->store->store(json_encode($resource), $resource->getUniqueIdentifier());
        $this->eventDispatcher->dispatch(self::EVENT_REGISTRATION, new Registration($resource));
      }
      else {
        throw new \Exception("A resource with identifier {$identifier} and perspective {$perspective} already exists.");
      }
    }
    else {
      throw new \Exception("A resource with identifier {$identifier} was not found.");
    }
  }

  /**
   *
   */
  public function registerNewVersion(Resource $resource) {
    $perspective = $resource->getPerspective();
    if ($perspective !== 'source') {
      throw new \Exception("Only versions of source resources are allowed.");
    }

    $identifier = $resource->getIdentifier();
    $version = $resource->getVersion();

    if ($this->exists($identifier, 'source')) {
      if (!$this->exists($identifier, 'source', $version)) {
        $this->store->store(json_encode($resource), $resource->getUniqueIdentifier());
        $this->eventDispatcher->dispatch(self::EVENT_REGISTRATION, new Registration($resource));
      }
      else {
        throw new \Exception("A resource with identifier {$identifier} and version {$version} already exists.");
      }
    }
    else {
      throw new \Exception("A resource with identifier {$identifier} was not found.");
    }
  }

  /**
   * Retrieve.
   */
  public function get(string $identifier, $perspective = 'source', $version = NULL): ?Resource {
    $data = $this->getFull($identifier, $perspective, $version);
    return ($data != FALSE) ? Resource::hydrate(json_encode($data)) : NULL;
  }

  /**
   *
   */
  private function getFull(string $identifier, $perspective, $version) {
    if (!$version) {
      $data = $this->getLatestRevision($identifier, $perspective);
    }
    else {
      $data = $this->getRevision($identifier, $perspective, $version);
    }
    return $data;
  }

  /**
   *
   */
  public function remove(Resource $resource) {
    if ($this->exists($resource->getIdentifier(), $resource->getPerspective(), $resource->getVersion())) {
      $this->store->remove($resource->getUniqueIdentifier());
    }
  }

  /**
   * Private.
   *
   * @return object || False
   */
  private function getLatestRevision($identifier, $perspective) {
    $query = $this->getCommonQuery($identifier, $perspective);
    $query->sortByDescending('version');
    $items = $this->store->query($query);
    return reset($items);
  }

  /**
   * Private.
   *
   * @return object || False
   */
  private function getRevision($identifier, $perspective, $version) {
    $query = $this->getCommonQuery($identifier, $perspective);
    $query->conditionByIsEqualTo('version', $version);
    $items = $this->store->query($query);
    return reset($items);
  }

  /**
   * Private.
   */
  private function getCommonQuery($identifier, $perspective) {
    $query = new Query();
    $query->properties = ['identifier', 'version', 'perspective', 'filePath', 'mimeType'];
    $query->conditionByIsEqualTo('identifier', $identifier);
    $query->conditionByIsEqualTo('perspective', $perspective);
    $query->limitTo(1);
    return $query;
  }

  /**
   *
   */
  private function filePathExists($filePath) {
    $query = new Query();
    $query->conditionByIsEqualTo('filePath', $filePath);
    $results = $this->store->query($query);
    return !empty($results);
  }

  /**
   * Private.
   */
  private function exists($identifier, $perspective, $version = NULL) {
    $item = $this->get($identifier, $perspective, $version);
    return isset($item) ? TRUE : FALSE;
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

  /*$directory = $this->getLocalDirectory($uuid);
  $this->drupalFiles->getFilesystem()
  ->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY);

  $this->getFileFetcher($uuid, $url);*/

}
