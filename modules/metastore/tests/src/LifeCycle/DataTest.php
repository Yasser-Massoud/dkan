<?php

namespace Drupal\Tests\metastore\LifeCycle;

use Drupal\common\UrlHostTokenResolver;
use Drupal\Core\DependencyInjection\Container;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Entity\EntityTypeRepository;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\Field\FieldItemList;
use Drupal\metastore\FileMapper;
use Drupal\metastore\NodeWrapper\Data;
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
class DataTest extends TestCase {

  /**
   * @var Chain
   */
  private $nodeChain;

  public function test() {
    $this->assertTrue(true);
  }


  /*public function testToken() {
    include(__DIR__ . "/../../metastore.module");

    $token = UrlHostTokenResolver::TOKEN;
    $storedUrl = "http://{$token}/hello.html";
    $realUrl = "http://mysite.test/hello.html";

    \Drupal::setContainer($this->getContainer($storedUrl));
    Node::load('1');

    print_r($this->nodeChain->getStoredInput('set'));

    //$this->assertEquals($realUrl,);
  }*/

  private function getContainer($downloadUrl) {

    $metadata = (object) [
      "data" => (object) [
        "downloadURL" => $downloadUrl,
      ],
    ];

    $nodeGet = (new Options())
      ->add('field_json_metadata', (object) ['value' => json_encode($metadata)])
      ->add('field_data_type', FieldItemList::class);

    $nodeChain = (new Chain($this))
      ->add(Node::class, 'id', '1')
      ->add(Node::class, 'bundle', 'data')
      ->add(Node::class, 'get', $nodeGet)
      ->add(FieldItemList::class, '__get', 'distribution')
      ->add(FieldItemList::class, 'setValue', null, 'set');

    $this->nodeChain = $nodeChain;

      $node = $nodeChain->getMock();

    $entityTypeManager = (new Chain($this))
        ->add(EntityTypeManager::class, 'getStorage', MyNodeStorage::class)
        ->add(MyNodeStorage::class, 'getFromStaticCache', [])
        ->add(MyNodeStorage::class, 'preLoad', [])
        ->add(MyNodeStorage::class, 'getFromPersistentCache', [])
        ->add(MyNodeStorage::class, 'getFromStorage', ['1' => $node])
        ->add(MyNodeStorage::class, 'setPersistentCache', null)
        ->add(MyNodeStorage::class, 'setStaticCache', null)
        ->getMock();

    $entityTypeRepository = (new Chain($this))
      ->add(EntityTypeRepository::class, 'getEntityTypeFromClass', null)
      ->getMock();

    $hooks = (new Options())
      ->add('entity_preload', [])
      ->add('entity_storage_load', [])
      ->add('node_storage_load', [])
      ->add('entity_load', [])
      ->add('node_load', ['metastore']);

    $moduleHandler = (new Chain($this))
      ->add(ModuleHandler::class, 'getImplementations', $hooks)
      ->getMock();

    $fileMapper = (new Chain($this))
      ->add(FileMapper::class, 'blah', null)
      ->getMock();

    $container = new Container();
    $container->set('entity_type.repository', $entityTypeRepository);
    $container->set('entity_type.manager', $entityTypeManager);
    $container->set('module_handler', $moduleHandler);
    $container->set('dkan.metastore.file_mapper', $fileMapper);

    return $container;
  }

}

class MyNodeStorage extends NodeStorage {
  protected $entityTypeId = 'node';
  protected $entityClass = Node::class;
}
