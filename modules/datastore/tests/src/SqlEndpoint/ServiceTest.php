<?php

namespace Drupal\Tests\datastore\SqlEndpoint;

use Drupal\common\Resource;
use Drupal\Component\DependencyInjection\Container;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\datastore\Service\ResourceLocalizer;
use Drupal\datastore\Storage\DatabaseTable;
use Drupal\datastore\Storage\DatabaseTableFactory;
use Drupal\metastore\FileMapper;
use Drupal\Tests\common\Traits\ServiceCheckTrait;
use Drupal\Tests\datastore\Traits\TestHelperTrait;
use Drupal\datastore\SqlEndpoint\Service;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class ServiceTest extends TestCase {
  use TestHelperTrait;
  use ServiceCheckTrait;

  /**
   *
   */
  public function testHappyPath() {

    $chain = $this->getContainerChainForService('dkan.datastore.sql_endpoint.service');

    $dbData = (object) [
      'first_name' => "Felix",
      'last_name' => "The Cat",
      'occupation' => "cat",
    ];

    $schema = [
      'fields' => [
        'first_name' => [
          'description' => 'First Name',
        ],
        'last_name' => [
          'description' => 'last_name',
        ],
        'occupation' => [],
      ],
    ];

    $container = $chain
      ->add(ResourceLocalizer::class, 'getFileMapper', FileMapper::class)
      ->add(ResourceLocalizer::class, 'get', Resource::class)
      ->add(FileMapper::class, 'get', Resource::class)
      ->add(Container::class, "get", $this->getServices())
      ->add(ConfigFactory::class, "get", ImmutableConfig::class)
      ->add(ImmutableConfig::class, "get", "100")
      ->add(Resource::class, 'getUniqueIdentifier', 'axw_123_local_file')
      ->add(DatabaseTableFactory::class, 'getInstance', DatabaseTable::class)
      ->add(DatabaseTable::class, 'query', [$dbData])
      ->add(DatabaseTable::class, 'getSchema', $schema)
      ->getMock();

    $expectedData = (object) [
      'First Name' => "Felix",
      'last_name' => "The Cat",
      'occupation' => "cat",
    ];

    $service = Service::create($container);
    $data = $service->runQuery('[SELECT * FROM 123][WHERE last_name = "Felix"][ORDER BY first_name DESC][LIMIT 1 OFFSET 1];');
    $this->assertEquals($expectedData, $data[0]);
  }

  /*public function testParserInvalidQueryString() {
  $container = $this->getCommonMockChain($this)
  ->add(SqlParser::class, 'validate', FALSE)
  ->getMock();

  $service = Service::create($container);
  $this->expectExceptionMessage("Invalid query string.");
  $service->runQuery('[SELECT FROM 123');
  }

  public function testGetDatabaseTableExceptionResourceNotFound() {
  $container = $this->getCommonMockChain($this)
  ->add(ResourceServiceFactory::class, 'getInstance', ResourceService::class)
  ->add(ResourceService::class, 'get', NULL)
  ->getMock();

  $service = Service::create($container);
  $this->expectExceptionMessage("Resource not found.");
  $service->runQuery('[SELECT * FROM 123][WHERE last_name = "Felix"][ORDER BY first_name DESC][LIMIT 1 OFFSET 1];');
  }

  public function testAutoLimitOnSqlStatements() {
  $container = $this->getCommonMockChain($this)
  ->getMock();

  $service = Service::create($container);
  $query = $service->getQueryObject("[SELECT * FROM blah];");
  $this->assertTrue(isset($query->limit));
  $this->assertEquals($query->limit, 100);
  }

  public function testNoAutoLimitOnCountSqlStatements() {
  $container = $this->getCommonMockChain($this)
  ->getMock();

  $service = Service::create($container);
  $query = $service->getQueryObject("[SELECT COUNT(*) FROM blah];");
  $this->assertFalse(isset($query->limit));
  }*/

}
