<?php

namespace Drupal\metastore;

use Drupal\common\Storage\DatabaseTableInterface;
use Drupal\common\Storage\Query;


/**
 * FileMapper.
 */
class FileMapper {


  private $store;

  /**
   * Constructor.
   */
  public function __construct(DatabaseTableInterface $store) {
    $this->store = $store;
  }

  /**
   * Register a new url for mapping.
   */
  public function register(string $url) : array {

    $id = md5($url);

    $data = [
      'uuid' => $id,
      'revision' => time(),
      'url' => $url,
      'type' => 'source',
    ];

    if (!$this->exists($data['uuid'], $data['type'])) {

      $this->store->store(json_encode((object) $data), $id);
      return [$data['uuid'], $data['revision']];
    }
    throw new \Exception("URL already registered.");
  }

  public function registerNewPerspective($url, $uuid, $type, $revision = null) {
    if ($this->exists($uuid, 'source', $revision)) {
      if (!$this->exists($uuid, $type, $revision)) {
        $item = $this->getFull($uuid, 'source', $revision);
        $item['type'] = $type;
        $item['url'] = $url;
        $this->store->store(json_encode((object) $item), md5($item['url'] . $type));
      }
    }
    else {
      throw new \Exception("A URL with uuid {$uuid} was not found.");
    }
  }

  public function addRevision($uuid) {
    if ($this->exists($uuid, 'source')) {
      $item = $this->getLatestRevision($uuid, 'source');
      $newRevision = time();
      if ($newRevision == $item['revision']) {
        $newRevision++;
      }
      $item['revision'] = $newRevision;
      $this->store->store(json_encode((object) $item), md5($item['url'] . $item['revision']));
      return $item['revision'];
    }
    throw new \Exception("Url with uuid {$uuid} does not exist");
  }

  /**
   * Retrieve.
   */
  public function get(string $uuid, $type = 'source', $revision = null) {
    $data = $this->getFull($uuid, $type, $revision);
    return ($data != FALSE) ? $data['url'] : NULL;
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
   * @return array || False
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
   * @return array || False
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
