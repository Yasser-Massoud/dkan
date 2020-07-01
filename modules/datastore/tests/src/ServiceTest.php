<?php

namespace Drupal\Tests\datastore;

use Drupal\common\Resource;
use Drupal\Core\Queue\QueueFactory;
use Drupal\datastore\Service;
use Drupal\datastore\Service\Factory\Import as ImportServiceFactory;
use Drupal\datastore\Service\Import as ImportService;
use Drupal\metastore\FileMapper;
use Drupal\Tests\common\Traits\ServiceCheckTrait;
use FileFetcher\FileFetcher;
use PHPUnit\Framework\TestCase;
use Procrastinator\Result;
use Drupal\datastore\Service\ResourceLocalizer;

/**
 *
 */
class ServiceTest extends TestCase {
  use ServiceCheckTrait;

  /**
   *
   */
  public function testImport() {

    $chain = $this->getContainerChainForService('dkan.datastore.service')
      ->add(ResourceLocalizer::class, 'getFileMapper', FileMapper::class)
      ->add(ResourceLocalizer::class, 'localize', NULL)
      ->add(ResourceLocalizer::class, 'getFileFetcher', FileFetcher::class)
      ->add(FileFetcher::class, 'run', Result::class)
      ->add(FileMapper::class, 'get', Resource::class)
      ->add(ImportServiceFactory::class, "getInstance", ImportService::class)
      ->add(ImportService::class, "import", NULL)
      ->add(ImportService::class, "getResult", new Result())
      ->add(QueueFactory::class, "get", NULL);

    $service = Service::create($chain->getMock());
    $result = $service->import("1");

    $this->assertTrue(is_array($result));
  }

  /*public function testDeferredImport() {

  $chain = (new Chain($this))
  ->add(Container::class, "get", $this->getContainerOptions())
  ->add(ResourceServiceFactory::class, "getInstance", ResourceService::class)
  ->add(ResourceService::class, "get", new Resource("1", "file:///hello.txt", "text/csv"))
  ->add(ImportServiceFactory::class, "getInstance", ImportService::class)
  ->add(QueueFactory::class, "get", Memory::class)
  ->add(Memory::class, "createItem", "123");

  $service = Service::create($chain->getMock());
  $result = $service->import("1", TRUE);

  $this->assertTrue(is_array($result));
  }

  public function testDrop() {
  $container = (new Chain($this))
  ->add(Container::class, "get", $this->getContainerOptions())
  ->add(QueueFactory::class, "get", NULL)
  ->add(ResourceServiceFactory::class, "getInstance", ResourceService::class)
  ->add(ResourceService::class, "get", new Resource("1", "file:///hello.txt", "text/csv"))
  ->add(ResourceService::class, "remove", NULL)
  ->add(ImportServiceFactory::class, "getInstance", ImportService::class)
  ->add(ImportService::class, "getStorage", DatabaseTable::class)
  ->add(DatabaseTable::class, "destroy", NULL)
  ->add(JobStoreFactory::class, "getInstance", JobStore::class)
  ->add(JobStore::class, "remove", NULL)
  ->getMock();

  $service = Service::create($container);
  $service->drop("1");

  $this->assertTrue(TRUE);
  }*/

}
