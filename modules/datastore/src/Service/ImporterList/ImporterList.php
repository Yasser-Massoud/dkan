<?php

namespace Drupal\datastore\Service\ImporterList;

use Drupal\datastore\Service\Factory\Import;
use Drupal\common\Storage\JobStoreFactory;
use Drupal\datastore\Service\ResourceLocalizer;
use FileFetcher\FileFetcher;

/**
 * Definition of an "importer list" that allows for reporting.
 */
class ImporterList {

  /**
   * A JobStore object.
   *
   * @var \Drupal\common\Storage\JobStore
   */
  private $jobStoreFactory;

  private $resourceLocalizer;
  private $importServiceFactory;

  /**
   * Constructor.
   */
  public function __construct(JobStoreFactory $jobStoreFactory, ResourceLocalizer $resourceLocalizer, Import $importServiceFactory) {
    $this->jobStoreFactory = $jobStoreFactory;
    $this->resourceLocalizer = $resourceLocalizer;
    $this->importServiceFactory = $importServiceFactory;
  }

  /**
   * Retrieve stored jobs and build the list array property.
   *
   * @return array
   *   An array of ImporterListItem objects, keyed by UUID.
   */
  private function buildList() {
    $list = [];

    $fileFetchers = [];
    $importers = [];

    $store = $this->jobStoreFactory->getInstance(FileFetcher::class);
    foreach ($store->retrieveAll() as $id) {
      try {
        $resource = $this->resourceLocalizer->getByUniqueIdentifier($id);
        $fileFetchers[$id] = $this->resourceLocalizer->getFileFetcher($resource);

        $importers[$id] = $this->importServiceFactory->getInstance($resource->getUniqueIdentifier(),
          ['resource' => $resource])->getImporter();
      }
      catch (\Exception $e) {
        // The file fetcher id is not a resource.
      }
    }

    foreach ($fileFetchers as $uuid => $fileFetcher) {
      $importer = isset($importers[$uuid]) ? $importers[$uuid] : NULL;
      $list[$uuid] = ImporterListItem::getItem($fileFetcher, $importer);
    }

    return $list;
  }

  /**
   * Static function to allow easy creation of lists.
   */
  public static function getList(JobStoreFactory $jobStoreFactory, ResourceLocalizer $resrouceLocalizer, Import $importServiceFactory): array {
    $importerLister = new ImporterList($jobStoreFactory, $resrouceLocalizer, $importServiceFactory);
    return $importerLister->buildList();
  }

}
