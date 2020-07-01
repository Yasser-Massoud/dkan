<?php

namespace Drupal\Tests\metastore;

use Drupal\common\Resource;
use Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher;
use Drupal\Core\DependencyInjection\Container;
use Drupal\metastore\FileMapper;
use PHPUnit\Framework\TestCase;
use Drupal\Tests\metastore\Unit\DatabaseTableMock;

/**
 *
 */
class FileMapperTest extends TestCase {

  /**
   *
   */
  public function test() {
    $url = "http://blah.blah/file/blah.csv";
    $url2 = "http://blah.blah/file/blah2.csv";
    $localUrl = "https://dkan.dkan/resources/file/blah.csv";
    $localUrl2 = "https://dkan.dkan/resources/file/newblah.csv";

    $store = new DatabaseTableMock();
    $eventDispatcher = new ContainerAwareEventDispatcher(new Container());
    $filemapper = new FileMapper($store, $eventDispatcher);

    // Register a resource.
    $resource1 = $this->getResource($url);
    $this->registerResource($resource1, $filemapper);

    // Can't register the same url twice.
    try {
      $filemapper->register($this->getResource($url));
      $this->assertTrue(FALSE);
    }
    catch (\Exception $e) {
      $this->assertEquals("URL already registered.", $e->getMessage());
    }

    // Register a second url.
    $resource2 = $this->getResource($url2);
    $this->registerResource($resource2, $filemapper);

    // Register a different perspective of the first resource.
    $resource1local = $resource1->createNewPerspective('local_url', $localUrl);
    $filemapper->registerNewPerspective($resource1local);
    $this->retrieveAndCheck($resource1, $filemapper);
    $this->retrieveAndCheck($resource1local, $filemapper);

    // Add a new revision of the first url.
    $resource1v2 = $resource1->createNewVersion();
    $filemapper->registerNewVersion($resource1v2);
    $this->retrieveAndCheck($resource1, $filemapper);
    $this->retrieveAndCheck($resource1v2, $filemapper);
    $this->assertNotEquals($resource1->getVersion(), $resource1v2->getVersion());

    // Should be able to get local from first revision but not second.
    $this->assertEquals($localUrl,
      $filemapper->get($resource1->getIdentifier(), 'local_url', $resource1->getVersion())
        ->getFilePath()
    );
    $this->assertNull($filemapper->get($resource1v2->getIdentifier(), 'local_url', $resource1v2->getVersion()));

    // Add perspective to the new revision.
    $resource1v2local = $resource1v2->createNewPerspective('local_url', $localUrl2);
    $filemapper->registerNewPerspective($resource1v2local);
    $this->assertEquals($localUrl,
      $filemapper->get($resource1local->getIdentifier(), 'local_url', $resource1local->getVersion())
        ->getFilePath());
    $this->assertEquals($localUrl2,
      $filemapper->get($resource1v2local->getIdentifier(), 'local_url', $resource1v2local->getVersion())
        ->getFilePath());

    // The file mapper should not register other perspectives as sources.
    try {
      $filemapper->register($this->getResource($localUrl));
      $this->assertTrue(FALSE);
    }
    catch (\Exception $e) {
      $this->assertEquals("URL already registered.", $e->getMessage());
    }

  }

  /**
   *
   */
  private function registerResource($resource, $filemapper) {
    $success = $filemapper->register($resource);
    $this->assertTrue($success);
    $this->retrieveAndCheck($resource, $filemapper);
  }

  /**
   *
   */
  private function retrieveAndCheck(Resource $resource, $filemapper) {
    $retrieved = $filemapper->get($resource->getIdentifier(), $resource->getPerspective(), $resource->getVersion());
    $this->assertEquals($resource, $retrieved);
  }

  /**
   *
   */
  private function getResource($url) {
    return new Resource($url, 'text/csv', 'source');
  }

}
