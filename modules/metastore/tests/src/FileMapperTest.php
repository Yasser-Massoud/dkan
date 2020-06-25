<?php

namespace Drupal\Tests\metastore;

use Contracts\HydratableInterface;
use Contracts\Mock\Storage\JsonObjectMemory;
use Contracts\Mock\Storage\Memory;
use Drupal\common\Storage\AbstractDatabaseTable;
use Drupal\common\Storage\DatabaseTableInterface;
use Drupal\common\Storage\JobStoreFactory;
use Drupal\common\Storage\Query;
use Drupal\common\Util\DrupalFiles;
use Drupal\Core\DependencyInjection\Container;
use Drupal\Core\DrupalKernel;
use Drupal\Core\Site\Settings;
use Drupal\Core\StreamWrapper\PublicStream;
use Drupal\Core\StreamWrapper\StreamWrapperManager;
use Drupal\metastore\FileMapper;
use Drupal\Tests\common\Unit\Mocks\FileSystem;
use Drupal\Tests\metastore\Unit\ProcessorMock;
use MockChain\Chain;
use MockChain\ReturnNull;
use MockChain\Sequence;
use PHPUnit\Framework\TestCase;

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

    $filemapper = new FileMapper($store);

    // Registre a url.
    [$uuid, $revision] = $filemapper->register($url);
    $this->assertEquals($url, $filemapper->get($uuid));
    $this->assertNotEmpty($revision);

    // Can't register the same url twice.
    try {
      $filemapper->register($url);
      $this->assertTrue(FALSE);
    }
    catch(\Exception $e) {
      $this->assertEquals("URL already registered.", $e->getMessage());
    }

    // Register a second url.
    [$uuid2, $revision2] = $filemapper->register($url2);
    $this->assertEquals($url2, $filemapper->get($uuid2));
    $this->assertNotEmpty($revision2);

    // Register a different perspective/type of the first url.
    $filemapper->registerNewPerspective($localUrl, $uuid, 'local_url');
    $this->assertEquals($url, $filemapper->get($uuid));
    $this->assertEquals($localUrl, $filemapper->get($uuid, 'local_url'));

    // Add a new revision of the first url.
    $revisionNew = $filemapper->addRevision($uuid);
    $this->assertGreaterThan($revision, $revisionNew);
    $urlNew = $filemapper->get($uuid, 'source', $revisionNew);
    $this->assertEquals($url, $urlNew);

    // should be able to get local from first revision but not second.
    $this->assertEquals($localUrl, $filemapper->get($uuid, 'local_url', $revision));
    $this->assertNull($filemapper->get($uuid, 'local_url', $revisionNew));

    // Add perspective/type to the new revision.
    $filemapper->registerNewPerspective($localUrl2, $uuid, 'local_url', $revisionNew);
    $this->assertEquals($localUrl, $filemapper->get($uuid, 'local_url', $revision));
    $this->assertEquals($localUrl2, $filemapper->get($uuid, 'local_url', $revisionNew));
  }

  /*private $store;
  private $container;

  public function setUp() {
    parent::setUp();

    $settings['file_public_base_url'] = "http://local.local/files";
    new Settings($settings);

    \Drupal::setContainer($this->getContainer());
  }

  public function test() {
    $fileMapper = $this->getFileMapper();
    $url = 'http://test.test/filename.ext';

    // Register a URL with the FileMapper.
    $uuid = $fileMapper->register($url);

    $this->assertEquals(md5($url), $uuid);
    $this->assertNotEmpty($this->store->retrieve($uuid));
    $this->assertEquals($url, $fileMapper->getSource($uuid));

    $ff = $fileMapper->getFileFetcher($uuid);
    $destination = $ff->getStateProperty('destination');

    $fileSubPath = "files/resources/{$uuid}/test_test_filename.ext";

    // @todo clear filefetcher's file name generation.
    $this->assertEquals(__DIR__ . "/" . $fileSubPath, $destination);

    // The file has not been downloaded, we do not know the local URL.
    try {
      $fileMapper->getLocalUrl($uuid);
      $this->assertTrue(FALSE);
    }
    catch (\Exception $e) {
      $this->assertEquals('Unknown URL.', $e->getMessage());
    }

    // Download the file by running the FileFetcher.
    $ff->run();

    // Now we should have a local URL for the file.
    $localUrl = $fileMapper->getLocalUrl($uuid);
    $this->assertEquals("http://local.local/{$fileSubPath}", $localUrl);

   try {
      $fileMapper->register($url);
      $this->assertTrue(FALSE);
    }
    catch (\Exception $e) {
      $this->assertEquals('URL already registered.', $e->getMessage());
    }
  }


  private function getFileMapper() {
    $this->store = new JsonObjectMemory();

    $jobStoreFactory = (new Chain($this))
      ->add(JobStoreFactory::class, "getInstance", $this->store)
      ->getMock();

    $drupalFiles = DrupalFiles::create($this->getContainer());

    $processors = [
      ProcessorMock::class,
    ];

    $fileMapper = new FileMapper($jobStoreFactory, $drupalFiles);
    $fileMapper->setFileFetcherProcessors($processors);
    return $fileMapper;
  }


  private function getContainer() {
    if (!$this->container) {
      $streamWrapperManager = new StreamWrapperManager();

      $kernel = (new Chain($this))
        ->add(DrupalKernel::class, 'handle', "blah")
        ->getMock();

      $container = new Container();
      $container->setParameter('site.path', __DIR__);
      $container->set('stream_wrapper_manager', $streamWrapperManager);
      $container->set('stream_wrapper.public', new PublicStream());

      $filesystem = new FileSystem($this, $container);
      $container->set('file_system', $filesystem);

      $container->set('kernel', $kernel);
      $container->set('site.path', new Stringy(__DIR__));

      $streamWrapperManager->setContainer($container);
      $streamWrapperManager->addStreamWrapper('stream_wrapper.public', PublicStream::class, 'public');

      $this->container = $container;
    }
    return $this->container;
  }*/

}

/*class Stringy {
  private $string;


  public function __construct($string) {
    $this->string = $string;
  }


  public function __toString() {
    return $this->string;
  }

}*/

class DatabaseTableMock implements DatabaseTableInterface {
  private $store = [];

  public function retrieveAll(): array {
  }

  public function storeMultiple(array $data) {
    // TODO: Implement storeMultiple() method.
  }

  public function count(): int {
    // TODO: Implement count() method.
  }

  public function destroy() {
    // TODO: Implement destroy() method.
  }

  public function query(Query $query) {
    $storeCopy = $this->store;

    foreach ($query->conditions as $property => $value) {
      $storeCopy = array_filter($storeCopy, function ($item) use ($property, $value) {
        return $item[$property] == $value;
      });
    }

    $sortProperty = reset($query->sort['DESC']);

    if ($sortProperty) {
      usort($storeCopy, function ($a, $b) use ($sortProperty) {
        return strcmp($a[$sortProperty], $b[$sortProperty]);
      });
    }

    return $storeCopy;
  }

  public function remove(string $id) {
    // TODO: Implement remove() method.
  }

  public function retrieve(string $id) {
    // TODO: Implement retrieve() method.
  }

  public function setSchema($schema) {
    // TODO: Implement setSchema() method.
  }

  public function getSchema() {
    // TODO: Implement getSchema() method.
  }

  public function store($data, string $id = NULL): string {
    $this->store[$id] = (array) json_decode($data);
    return $id;
  }

}
