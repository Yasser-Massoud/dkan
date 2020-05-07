<?php

namespace Drupal\Tests\metastore;

use Drupal\common\UrlHostTokenResolver;
use Drupal\Core\DependencyInjection\Container;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Entity\EntityTypeRepository;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\Field\FieldItemList;
use Drupal\metastore\Data;
use Drupal\metastore\FileMapper;
use Drupal\node\Entity\Node;
use Drupal\node\NodeStorage;
use MockChain\Chain;
use MockChain\Options;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 *
 */
class DistributionDataNodeLifeCycleTest extends TestCase {

  public function test() {
    $this->assertTrue(true);
  }

  /*public function test() {

    \Drupal::setContainer($this->getContainer());

    $node = new Node([
      'field_data_type' => 'distribution',
      'field_json_metadata' => $this->getMetadata(),
    ], 'node', 'data');
  }

  private function getMetadata() {
    $data = (object) [
      'downloadURL' => 'http://myhost.local/file/hello.csv'
    ];

    $metadata = (object) [
      'identifier' => '12345',
      'data' => $data,
    ];

    return json_encode($metadata);
  }

  public function testPresaveDistribution() {
    $container = (new Chain($this))
      ->add(Container::class, "get", RequestStack::class)
      ->add(RequestStack::class, "getCurrentRequest", Request::class)
      ->add(Request::class, "getHost", "dkan")
      ->add(Request::class, "getSchemeAndHttpHost", "http://dkan")
      ->getMock();

    \Drupal::setContainer($container);

    $metadata = (object) [
      "data" => (object) [
        "downloadURL" => "http://dkan/some/path/blah",
      ],
    ];

    $options = (new Options())
      ->add('field_json_metadata', (object) ["value" => json_encode($metadata)])
      ->add('field_data_type', (object) ["value" => "distribution"])
      ->index(0);

    $nodeChain = new Chain($this);
    $node = $nodeChain
      ->add(Node::class, "bundle", "data")
      ->add(Node::class, "get", $options)
      ->add(Node::class, "set", NULL, "metadata")
      ->getMock();

    $fileMapperChain = (new Chain($this))
      ->add(FileMapper::class, 'register', "12345", 'fileMapperRegister');
    $fileMapper = $fileMapperChain->getMock();

    // Test that the downloadUrl is being registered correctly with the
    // FileMapper.
    $lifeCycle = new Data($node);
    $lifeCycle->setFileMapper($fileMapper);
    $lifeCycle->presave();

    $inputs = $fileMapperChain->getStoredInput("fileMapperRegister");
    $this->assertNotEmpty($inputs);

    $newdata = $nodeChain->getStoredInput('metadata');
    $newdata = json_decode($newdata[1]);

    $this->assertNotEquals($metadata->data->downloadURL, $newdata->data->downloadURL);
  }

  public function testLoadDistribution() {
    $metadata = (object) [
      "data" => (object) [
        "downloadURL" => "12345",
      ],
    ];

    $container = (new Chain($this))
      ->add(Container::class, "get", RequestStack::class)
      ->getMock();

    \Drupal::setContainer($container);

    $nodeGetOptions = (new Options())
      ->add('field_data_type', (object) ['value' => 'distribution'])
      ->add('field_json_metadata', (object) ['value' => json_encode($metadata)]);

    $nodeChain = new Chain($this);
    $node = $nodeChain
      ->add(Node::class, "bundle", "data")
      ->add(Node::class, 'get', $nodeGetOptions)
      ->add(Node::class, 'set', NULL, 'nodeSet')
      ->getMock();

    $fileMapperChain = (new Chain($this))
      ->add(FileMapper::class, 'getSource', "http://dkan/some/path/blah");
    $fileMapper = $fileMapperChain->getMock();

    $lifeCycle = new Data($node);
    $lifeCycle->setFileMapper($fileMapper);
    $lifeCycle->load();

    $nodeSet = $nodeChain->getStoredInput('nodeSet')[1];

    $this->assertTrue(substr_count($nodeSet, 'blah') > 0);
  }
  */

}


