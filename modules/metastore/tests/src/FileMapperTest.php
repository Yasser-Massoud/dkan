<?php

namespace Drupal\Tests\metastore;

use Contracts\Mock\Storage\JsonObjectMemory;
use Drupal\common\Storage\JobStoreFactory;
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
use PHPUnit\Framework\TestCase;

/**
 *
 */
class FileMapperTest extends TestCase {

  private $store;
  private $container;

  /**
   *
   */
  public function setUp() {
    parent::setUp();

    $settings['file_public_base_url'] = "http://local.local/files";
    new Settings($settings);

    \Drupal::setContainer($this->getContainer());
  }

  /**
   *
   */
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

    /**
 *Can not register the same URL twice.
 */
    try {
      $fileMapper->register($url);
      $this->assertTrue(FALSE);
    }
    catch (\Exception $e) {
      $this->assertEquals('URL already registered.', $e->getMessage());
    }
  }

  /**
   *
   */
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

  /**
   *
   */
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
  }

}
/**
 *
 */
class Stringy {
  private $string;

  /**
   *
   */
  public function __construct($string) {
    $this->string = $string;
  }

  /**
   *
   */
  public function __toString() {
    return $this->string;
  }

}
