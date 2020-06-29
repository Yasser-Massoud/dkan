<?php

namespace Drupal\metastore\LifeCycle;

use Drupal\common\UrlHostTokenResolver;
use Drupal\metastore\Reference\Dereferencer;
use Drupal\metastore\Traits\FileMapperTrait;

/**
 * Data.
 */
class Data extends AbstractData {
  use FileMapperTrait;

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

  /**
   * Private.
   */
  protected function datasetLoad() {
    $metadata = $this->data->getMetaData();

    // Dereference dataset properties.
    $referencer = \Drupal::service("metastore.dereferencer");
    $metadata = $referencer->dereference($metadata, dereferencing_method());

    $this->data->setMetadata($metadata);
  }

  /**
   * Private.
   *
   * @todo Decouple "resource" functionality from specific dataset properties.
   */
  protected function distributionLoad() {
    $metadata = $this->data->getMetaData();

    $fileMapperInfo = $metadata->data->downloadURL;
    \Drupal::service('logger.channel.default')->notice(json_encode($fileMapperInfo));
    if (is_array($fileMapperInfo)) {
      $url = $this->getFileMapper()->get($fileMapperInfo[0], 'source', $fileMapperInfo[1]);
      \Drupal::service('logger.channel.default')->notice(json_encode($url));
      if ($url) {
        $metadata->data->downloadURL = $url;
      }
      else {
        $metadata->data->downloadURL = "";
      }
    }

    $metadata->data->downloadURL = UrlHostTokenResolver::resolve($metadata->data->downloadURL);

    $this->data->setMetadata($metadata);
  }

  /**
   * Private.
   */
  protected function datasetPresave() {
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

    $referencing_method = dereferencing_method();
    if ($referencing_method == Dereferencer::DEREFERENCE_OUTPUT_REFERENCE_IDS) {
      $metadata = $this->addNodeModifiedDate($metadata);
    }

    $this->data->setMetadata($metadata);

    // Check for possible orphan property references when updating a dataset.
    /*if ($raw = $this->data->getRawMetadata()) {
      $orphanChecker = \Drupal::service("metastore.orphan_checker");
      $orphanChecker->processReferencesInUpdatedDataset(
        $raw,
        $metadata
      );
    }*/
  }

  /**
   * Private.
   */
  protected function distributionPresave() {
    $metadata = $this->data->getMetaData();

    if (isset($metadata->data->downloadURL)) {
      $downloadUrl = $metadata->data->downloadURL;

      // Modify local urls to use our host/shost scheme.
      $downloadUrl = $this->hostify($downloadUrl);

      try {
        // Register the url with the filemapper.
        $downloadUrl = $this->getFileMapper()
          ->register($downloadUrl);
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
