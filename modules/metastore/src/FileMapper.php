<?php

namespace Drupal\metastore;

use Dkan\Datastore\Resource;
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
  public function register(Resource $resource) : array {
    $id = $resource->getId();
    $url = $resource->getFilePath();
    $data = [
      'uuid' => $id,
      'revision' => time(),
      'url' => $url,
      'type' => 'source',
      'resource' => $resource
    ];

    if (!$this->urlExists($url)) {

      $this->store->store(json_encode((object) $data), $id);
      $this->eventDispatcher->dispatch(self::EVENT_REGISTRATION, new Registration($data));

      return [$data['uuid'], $data['revision']];
    }
    throw new \Exception("URL already registered.");
  }

  public function registerNewPerspective(Resource $resource, $type, $revision = null) {
    $uuid = $resource->getId();
    if ($this->exists($uuid, 'source', $revision)) {
      if (!$this->exists($uuid, $type, $revision)) {
        $original = $this->getFull($uuid, 'source', $revision);
        $item = clone $original;
        $item->type = $type;
        $item->url = $resource->getFilePath();
        $item->resource = $resource;

        $this->eventDispatcher->dispatch(self::EVENT_REGISTRATION, new Registration($item));
        $this->store->store(json_encode((object) $item), md5($item->url . $type));
      }
    }
    else {
      throw new \Exception("A URL with uuid {$uuid} was not found.");
    }
  }

  public function addRevision($uuid) {
    if ($this->exists($uuid, 'source')) {
      $original = $this->getLatestRevision($uuid, 'source');
      $item = clone $original;
      $newRevision = time();
      if ($newRevision == $item->revision) {
        $newRevision++;
      }
      $item->revision = $newRevision;

      $this->eventDispatcher->dispatch(self::EVENT_REGISTRATION, new Registration($item));
      $this->store->store(json_encode((object) $item), md5($item->url . $item->revision));

      return $item->revision;
    }
    throw new \Exception("Url with uuid {$uuid} does not exist");
  }

  /**
   * Retrieve.
   */
  public function get(string $uuid, $type = 'source', $revision = null): ?Resource {
    $data = $this->getFull($uuid, $type, $revision);
    return ($data != FALSE) ? Resource::hydrate(json_encode($data->resource)) : NULL;
  }

  private function getFull(string $uuid, $type, $revision) {
    if (!$revision) {
      $data = $this->getLatestRevision($uuid, $type);
    }
    else {
      $data = $this->getRevision($uuid, $type, $revision);
    }
    return $data;
  }

  /**
   * Private.
   *
   * @return object || False
   */
  private function getLatestRevision($uuid, $type) {
    $query = $this->getCommonQuery($uuid, $type);
    $query->sortByDescending('revision');
    $items = $this->store->query($query);
    return reset($items);
  }

  /**
   * Private.
   *
   * @return object || False
   */
  private function getRevision($uuid, $type, $revision)  {
    $query = $this-> getCommonQuery($uuid, $type);
    $query->conditionByIsEqualTo('revision', $revision);
    $items = $this->store->query($query);
    return reset($items);
  }

  /**
   * Private.
   */
  private function getCommonQuery($uuid, $type) {
    $query = new Query();
    $query->properties = ['uuid', 'revision', 'type', 'url'];
    $query->conditionByIsEqualTo('uuid', $uuid);
    $query->conditionByIsEqualTo('type', $type);
    $query->limitTo(1);
    return $query;
  }

  private function urlExists($url) {
    $query = new Query();
    $query->conditionByIsEqualTo('url', $url);
    $results = $this->store->query($query);
    return !empty($results);
  }

  /**
   * Private.
   */
  private function exists($uuid, $type, $revision = null) {
    $item = $this->get($uuid, $type, $revision);
    return isset($item) ? true : false;
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
