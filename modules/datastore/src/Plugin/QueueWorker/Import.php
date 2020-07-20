<?php

namespace Drupal\datastore\Plugin\QueueWorker;

use Drupal\common\LoggerTrait;
use Drupal\common\Resource;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\metastore\FileMapper;
use Procrastinator\Result;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Processes resource import.
 *
 * @QueueWorker(
 *   id = "datastore_import",
 *   title = @Translation("Queue to process datastore import"),
 *   cron = {"time" = 60}
 * )
 */
class Import extends QueueWorkerBase implements ContainerFactoryPluginInterface {
  use LoggerTrait;

  private $container;

  /**
   * Inherited.
   *
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $me = new Import($configuration, $plugin_id, $plugin_definition, $container);
    $me->setLoggerFactory($container->get('logger.factory'));
    return $me;
  }

  /**
   * Constructs a \Drupal\Component\Plugin\PluginBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   A dependency injection container.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ContainerInterface $container) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->container = $container;
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {

    try {
      $uid = $data->data['uuid'];

      /* @var $fileMapper FileMapper */
      $resrouceInfo = Resource::parseUniqueIdentifier($uid);

      /** @var \Drupal\datastore\Service $datastore */
      $datastore = $this->container->get('datastore.service');

      $results = $datastore->import($resrouceInfo['identifier'], FALSE, $resrouceInfo['version']);

      foreach ($results as $result) {
        $this->processResult($result, $data);
      }
    }
    catch (\Exception $e) {
      $this->log(RfcLogLevel::ERROR,
        "Import for {$data['uuid']} returned an error: {$e->getMessage()}");
    }
  }

  /**
   * Private.
   */
  private function processResult(Result $result, $data) {
    $level = RfcLogLevel::INFO;
    $message = "";
    $status = $result->getStatus();
    switch ($status) {
      case Result::STOPPED:
        $newQueueItemId = $this->requeue($data);
        $message = "Import for {$data->data['uuid']} is requeueing for iteration No. {$data['queue_iteration']}. (ID:{$newQueueItemId}).";
        break;

      case Result::IN_PROGRESS:
      case Result::ERROR:
        $level = RfcLogLevel::ERROR;
        $message = "Import for {$data->data['uuid']} returned an error: {$result->getError()}";
        break;

      case Result::DONE:
        $message = "Import for {$data->data['uuid']} completed.";
        break;
    }
    $this->log('dkan', $message, [], $level);
  }

  /**
   * Requeues the job with extra state information.
   *
   * @param array $data
   *   Queue data.
   *
   * @return mixed
   *   Queue ID or false if unsuccessfull.
   *
   * @todo: Clarify return value. Documentation suggests it should return ID.
   */
  protected function requeue(array $data) {
    return $this->container->get('queue')
      ->get($this->getPluginId())
      ->createItem($data);
  }

}
