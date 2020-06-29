<?php

namespace Drupal\Tests\metastore\LifeCycle;

use Contracts\Mock\Storage\Memory;
use Drupal\Component\DependencyInjection\Container;
use Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher;
use Drupal\metastore\FileMapper;
use Drupal\Tests\metastore\Unit\DatabaseTableMock;
use MockChain\Chain;
use PHPUnit\Framework\TestCase;
use Drupal\metastore\NodeWrapper\Data as Wrapper;
use Drupal\metastore\LifeCycle\Data;
use Drupal\common\UrlHostTokenResolver;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 *
 */
class DataTest extends TestCase {

  public function testPresaveDistribution() {
    \Drupal::setContainer($this->getContainer());

    $dataChain = $this->getDataChain();

    $lifeCycle = new Data($dataChain->getMock());
    $lifeCycle->presave();

    $metadata = $dataChain->getStoredInput("metadata");
    $this->assertTrue((substr_count($metadata[0]->data->downloadURL, UrlHostTokenResolver::TOKEN) > 0));
  }

  public function testPresaveDistributionFileMapper() {
    \Drupal::setContainer($this->getContainer());

    $dataChain = $this->getDataChain();

    $filemapper = new FileMapper(
      new DatabaseTableMock(),
      new ContainerAwareEventDispatcher(new Container())
    );

    $lifeCycle = new Data($dataChain->getMock());
    $lifeCycle->setFileMapper($filemapper);
    $lifeCycle->presave();

    $metadata = $dataChain->getStoredInput("metadata");
    $token = UrlHostTokenResolver::TOKEN;
    $url = "http://{$token}/some/path/blah";
    $this->assertEquals(md5($url), $metadata[0]->data->downloadURL[0]);
  }

  private function getContainer() {
    return (new Chain($this))
      ->add(Container::class, "get", RequestStack::class)
      ->add(RequestStack::class, "getCurrentRequest", Request::class)
      ->add(Request::class, "getHost", "dkan")
      ->add(Request::class, "getSchemeAndHttpHost", "http://dkan")
      ->getMock();
  }

  private function getDataChain() {
    $metadata = (object) [
      "data" => (object) [
        "downloadURL" => "http://dkan/some/path/blah",
      ],
    ];

    return (new Chain($this))
      ->add(Wrapper::class, 'getDataType', 'distribution')
      ->add(Wrapper::class, "setMetadata", NULL, "metadata")
      ->add(Wrapper::class, 'getMetadata', $metadata);
  }

}
