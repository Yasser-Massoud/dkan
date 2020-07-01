<?php

namespace Drupal\Tests\datastore\Service\ImporterList;

use Dkan\Datastore\Importer;
use Drupal\common\Resource;
use Drupal\common\Storage\JobStore;
use Drupal\common\Storage\JobStoreFactory;
use Drupal\datastore\Service\Factory\Import as ImportFactory;
use Drupal\datastore\Service\Import as ImportService;
use Drupal\datastore\Service\ImporterList\ImporterList;
use Drupal\datastore\Service\ResourceLocalizer;
use FileFetcher\FileFetcher;
use MockChain\Chain;
use MockChain\Options;
use MockChain\Sequence;
use PHPUnit\Framework\TestCase;
use Procrastinator\Result;

/**
 *
 */
class ImporterListTest extends TestCase {

  /**
   *
   */
  public function test() {

    $options = (new Options())
      ->add('total_bytes_copied', 20)
      ->add('total_bytes', 30)
      ->add("hello", "hello")
      ->add("source", "hello")
      ->index(0);

    $fileFetcher = (new Chain($this))
      ->add(FileFetcher::class, "getStateProperty", $options)
      ->add(FileFetcher::class, "getResult", Result::class)
      ->add(Result::class, "getStatus", Result::DONE)
      ->getMock();

    $sequence = new Sequence();
    $sequence->add(["1"]);
    $sequence->add([]);

    $jobStore = (new Chain($this))
      ->add(JobStore::class, "retrieveAll", $sequence)
      ->getMock();

    $jobStoreFactory = (new Chain($this))
      ->add(JobStoreFactory::class, "getInstance", $jobStore)
      ->getMock();

    $importServiceFactory = (new Chain($this))
      ->add(ImportFactory::class, "getInstance", ImportService::class)
      ->add(ImportService::class, "getImporter", Importer::class)
      ->getMock();

    $resourceLocalizer = (new Chain($this))
      ->add(ResourceLocalizer::class, 'getFileFetcher', FileFetcher::class)
      ->add(ResourceLocalizer::class, 'getByUniqueIdentifier', Resource::class)
      ->getMock();

    $list = ImporterList::getList($jobStoreFactory, $resourceLocalizer, $importServiceFactory);
    $this->assertTrue(is_array($list));
  }

}
