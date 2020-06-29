<?php

namespace Drupal\Tests\metastore;

use Dkan\Datastore\Resource;
use Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher;
use Drupal\Core\DependencyInjection\Container;
use Drupal\metastore\FileMapper;
use PHPUnit\Framework\TestCase;
use Drupal\Tests\metastore\Unit\DatabaseTableMock;

/**
 *
 */
class FileMapperTest extends TestCase {

  public function test() {
    $url = "http://blah.blah/file/blah.csv";
    $url2 = "http://blah.blah/file/blah2.csv";
    $localUrl = "https://dkan.dkan/resources/file/blah.csv";
    $localUrl2 = "https://dkan.dkan/resources/file/newblah.csv";

    $store = new DatabaseTableMock();

    $eventDispatcher = new ContainerAwareEventDispatcher(new Container());

    $filemapper = new FileMapper($store, $eventDispatcher);

    // Register a resource.
    [$uuid, $revision] = $filemapper->register($this->getResource($url));
    $this->assertEquals($this->getJson($url),
      json_encode($filemapper->get($uuid)));
    $this->assertNotEmpty($revision);

    // Can't register the same url twice.
    try {
      $filemapper->register($this->getResource($url));
      $this->assertTrue(FALSE);
    } catch (\Exception $e) {
      $this->assertEquals("URL already registered.", $e->getMessage());
    }

    // Register a second url.
    [$uuid2, $revision2] = $filemapper->register($this->getResource($url2));
    $this->assertEquals($this->getJson($url2),
      json_encode($filemapper->get($uuid2)));
    $this->assertNotEmpty($revision2);

    // Register a different perspective/type of the first url.
    $filemapper->registerNewPerspective($this->getResource($localUrl, $uuid),'local_url');
    $this->assertEquals($this->getJson($url),
      json_encode($filemapper->get($uuid)));
    $this->assertEquals($this->getJson($localUrl, $uuid), json_encode($filemapper->get($uuid, 'local_url')));

    // Add a new revision of the first url.
    $revisionNew = $filemapper->addRevision($uuid);
    $this->assertGreaterThan($revision, $revisionNew);
    $resourceNew = $filemapper->get($uuid, 'source', $revisionNew);
    $this->assertEquals($url, $resourceNew->getFilePath());

    // should be able to get local from first revision but not second.
    $this->assertEquals($localUrl,
      $filemapper->get($uuid, 'local_url', $revision)->getFilePath());
    $this->assertNull($filemapper->get($uuid, 'local_url', $revisionNew));

    // Add perspective/type to the new revision.
    $filemapper->registerNewPerspective($this->getResource($localUrl2, $uuid), 'local_url',
      $revisionNew);
    $this->assertEquals($localUrl,
      $filemapper->get($uuid, 'local_url', $revision)->getFilePath());
    $this->assertEquals($localUrl2,
      $filemapper->get($uuid, 'local_url', $revisionNew)->getFilePath());

    // The file mapper should not register other perspectives as sources.
    try {
      $filemapper->register($this->getResource($localUrl));
      $this->assertTrue(FALSE);
    } catch (\Exception $e) {
      $this->assertEquals("URL already registered.", $e->getMessage());
    }

  }

  private function getResource($url, $uuid = null) {
    if (!$uuid) {
      $uuid = md5($url);
    }
    return new Resource($uuid, $url, 'text/csv');
  }

  private function getJson($url, $uuid = null) {
    return json_encode($this->getResource($url, $uuid));
  }

}


