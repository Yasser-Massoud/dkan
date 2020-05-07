<?php

use Drupal\Component\DependencyInjection\Container;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemList;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Logger\LoggerChannel;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\datastore\LifeCycle\Data;
use Drupal\metastore\NodeWrapper\Data as Wrapper;
use Drupal\datastore\Service;
use Drupal\node\Entity\Node;
use MockChain\Chain;
use MockChain\Options;
use MockChain\Sequence;
use PHPUnit\Framework\TestCase;

class DataTest extends TestCase {

  public function testNonDistributionData() {
    $data = (new Chain($this))
      ->add(Wrapper::class, 'getDataType', 'blah')
      ->getMock();

    $cycle = new Data($data);

    $this->assertNull($cycle->insert());
    $this->assertNull($cycle->predelete());
  }

  public function testDistributionWithoutDownloadURL() {
    $metadata = (object) [
      'data' => (object) []
    ];
    $data = (new Chain($this))
      ->add(Wrapper::class, 'getDataType', 'distribution')
      ->add(Wrapper::class, 'getMetadata', $metadata)
      ->getMock();
    $cycle = new Data($data);
    $this->assertNull($cycle->insert());
  }

  public function testDistributionWithDownloadURL() {
    $options = (new Options())
      ->add("datastore.service", Service::class)
      ->add('logger.factory', LoggerChannelFactory::class)
      ->index(0);

    $containerChain = (new Chain($this))
      ->add(Container::class, 'get', $options)
      ->add(Service::class, 'import', new \Exception("Invalid metadata information or missing file information."))
      ->add(LoggerChannelFactory::class, 'get', LoggerChannel::class)
      ->add(LoggerChannel::class, 'log', NULL, 'log');

    $container = $containerChain->getMock();

    $metadata = (object) [
      'identifier' => "12345",
      'data' => (object) [
        'downloadURL' => "http://google.com",
        'mediaType' => "text/csv",
      ],
    ];

    \Drupal::setContainer($container);

    $data = (new Chain($this))
      ->add(Wrapper::class, 'getIdentifier', '12345')
      ->add(Wrapper::class, 'getDataType', 'distribution')
      ->add(Wrapper::class, 'getMetadata', $metadata)
      ->getMock();

    $cycle = new Data($data);
    $cycle->insert();

    $this->assertEquals('Invalid metadata information or missing file information.',
      $containerChain->getStoredInput('log')[1]);
  }


  public function testLifeCycle() {
    $options = (new Options())
      ->add('datastore.service', Service::class)
      ->add('logger.factory', LoggerChannelFactory::class)
      ->add('file_system', FileSystemInterface::class)
      ->index(0);

    $containerChain = (new Chain($this))
      ->add(Container::class, 'get', $options)
      ->add(Service::class, 'import', [], 'import')
      ->add(Service::class, 'drop', [], 'drop');
    $container = $containerChain->getMock();

    \Drupal::setContainer($container);

    $metadata = (object) [
      'identifier' => "12345",
      'data' => (object) [
        'downloadURL' => "http://google.com",
        'mediaType' => "text/csv",
      ],
    ];

    $data = (new Chain($this))
      ->add(Wrapper::class, 'getIdentifier', '12345')
      ->add(Wrapper::class, 'getDataType', 'distribution')
      ->add(Wrapper::class, 'getMetadata', $metadata)
      ->getMock();

    $cycle = new Data($data);
    $cycle->insert();
    // The right info was given to the datastore service to queue for import.
    $this->assertEquals(['12345', TRUE], $containerChain->getStoredInput('import'));

    $cycle->predelete();
    // The right info was given to the datastore service to drop the datastore.
    $this->assertEquals(['12345'], $containerChain->getStoredInput('drop'));
  }

}
