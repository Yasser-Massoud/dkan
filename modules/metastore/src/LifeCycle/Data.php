<?php

namespace Drupal\metastore\LifeCycle;

use Drupal\common\LoggerTrait;
use Drupal\common\Resource;
use Drupal\common\UrlHostTokenResolver;
use Drupal\metastore\Reference\Dereferencer;
use Drupal\metastore\Traits\FileMapperTrait;

/**
 * Data.
 */
class Data extends AbstractData {
  use FileMapperTrait;
  use LoggerTrait;

  /**
   * Load.
   */
  public function load() {
    $this->go('Load');
  }

  /**
   * Presave.
   *
   * Activities to move a data node through during presave.
   */
  public function presave() {
    $this->go('Presave');
  }

  public function predelete() {
    $this->go('predelete');
  }

  protected function datasetPredelete() {
    $raw = $this->data->getRawMetadata();
    $this->debug("@raw", ['@raw' => json_encode($raw)]);

    if (is_object($raw)) {
      $referencer = \Drupal::service("metastore.orphan_checker");
      $referencer->processReferencesInDeletedDataset($raw);
    }
  }

  protected function distributionPredelete() {
    $metadata = $this->data->getRawMetadata();
  }

  /**
   * Private.
   */
  protected function datasetLoad() {
    $metadata = $this->data->getMetaData();
    $this->debug(json_encode($metadata));

    // Dereference dataset properties.
    $referencer = \Drupal::service("metastore.dereferencer");
    $metadata = $referencer->dereference($metadata, dereferencing_method());

    $referencing_method = dereferencing_method();
    if ($referencing_method == Dereferencer::DEREFERENCE_OUTPUT_REFERENCE_IDS) {
      $metadata = $this->addNodeModifiedDate($metadata);
    }

    $this->data->setMetadata($metadata);
  }

  /**
   * Private.
   *
   * @todo Decouple "resource" functionality from specific dataset properties.
   */
  protected function distributionLoad() {
    $metadata = $this->data->getMetaData();
    $this->debug(json_encode($metadata));

    $fileMapperInfo = $metadata->data->downloadURL;
    \Drupal::service('logger.channel.default')->notice(json_encode($fileMapperInfo));
    if (is_array($fileMapperInfo)) {

      /* @var $resource Resource */
      $resource = $this->getFileMapper()->get($fileMapperInfo[0], 'source', $fileMapperInfo[1]);

      $referencing_method = dereferencing_method();
      if ($referencing_method == Dereferencer::DEREFERENCE_OUTPUT_REFERENCE_IDS) {
        $metadata->data->downloadURL = (object) [
          "identifier" => "{$fileMapperInfo[0]}__{$fileMapperInfo[1]}__source",
          "data" => $resource,
        ];
      }
      else {
        if ($resource) {
          $metadata->data->downloadURL = $resource->getFilePath();
        }
        else {
          $metadata->data->downloadURL = "";
        }
      }
    }

    $metadata->data->downloadURL = UrlHostTokenResolver::resolve($metadata->data->downloadURL);

    $this->data->setMetadata($metadata);
  }

  /**
   * Private.
   */
  protected function datasetPresave() {
    $this->debug();
    $metadata = $this->data->getMetaData();

    $title = isset($metadata->title) ? $metadata->title : $metadata->name;
    $this->data->setTitle($title);

    // If there is no uuid add one.
    if (!isset($metadata->identifier)) {
      $metadata->identifier = $this->data->getIdentifier();
    }
    // If one exists in the uuid it should be the same in the table.
    else {
      $this->data->setIdentifier($metadata->identifier);
    }

    $referencer = \Drupal::service("metastore.referencer");
    $metadata = $referencer->reference($metadata);

    $this->data->setMetadata($metadata);

    // Check for possible orphan property references when updating a dataset.
    if (!$this->data->isNew()) {
      $raw = $this->data->getRawMetadata();
      $orphanChecker = \Drupal::service("dkan.metastore.orphan_checker");
      $orphanChecker->processReferencesInUpdatedDataset(
        $raw,
        $metadata
      );
    }

  }

  /**
   * Private.
   */
  protected function distributionPresave() {
    $this->debug();
    $metadata = $this->data->getMetaData();

    if (isset($metadata->data->downloadURL)) {
      $downloadUrl = $metadata->data->downloadURL;

      // Modify local urls to use our host/shost scheme.
      $downloadUrl = $this->hostify($downloadUrl);

      $mimeType = "text/plain";
      if (isset($metadata->data->mediaType)) {
        $mimeType = $metadata->data->mediaType;
      }

      try {
        // Register the url with the filemapper.
        $resource = new Resource($downloadUrl, $mimeType);
        $this->debug("@resource", ['@resource' => json_encode($resource)]);

        if ($this->getFileMapper()->register($resource)) {
          $downloadUrl = [$resource->getIdentifier(), $resource->getVersion()];
        }
      }
      catch (\Exception $e) {
      }

      $metadata->data->downloadURL = $downloadUrl;
    }

    $this->data->setMetadata($metadata);
  }

  /**
   * Private.
   */
  private function hostify($url) {
    $host = \Drupal::request()->getHost();
    $parsedUrl = parse_url($url);
    if ($parsedUrl['host'] == $host) {
      $parsedUrl['host'] = UrlHostTokenResolver::TOKEN;
      $url = $this->unparseUrl($parsedUrl);
    }
    return $url;
  }

  /**
   * Private.
   */
  private function unparseUrl($parsedUrl) {
    $url = '';
    $urlParts = [
      'scheme',
      'host',
      'port',
      'user',
      'pass',
      'path',
      'query',
      'fragment',
    ];

    foreach ($urlParts as $part) {
      if (!isset($parsedUrl[$part])) {
        continue;
      }
      $url .= ($part == "port") ? ':' : '';
      $url .= ($part == "query") ? '?' : '';
      $url .= ($part == "fragment") ? '#' : '';
      $url .= $parsedUrl[$part];
      $url .= ($part == "scheme") ? '://' : '';
    }

    return $url;
  }

  /**
   * Private.
   */
  private function addNodeModifiedDate($metadata) {
    $formattedChangedDate = \Drupal::service('date.formatter')
      ->format($this->data->getModifiedDate(), 'html_date');
    $metadata->{'%modified'} = $formattedChangedDate;
    return $metadata;
  }

}
