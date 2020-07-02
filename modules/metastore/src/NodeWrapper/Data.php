<?php

namespace Drupal\metastore\NodeWrapper;

use Drupal\common\Exception\DataNodeLifeCycleEntityValidationException;
use Drupal\common\LoggerTrait;
use Drupal\Core\Entity\EntityInterface;
use Drupal\node\Entity\Node;

/**
 *
 */
class Data {
  use LoggerTrait;

  /**
   * @var \Drupal\node\Entity\Node
   */
  protected $node;

  /**
   * Constructor.
   */
  public function __construct(EntityInterface $entity) {
    $this->validate($entity);
    $this->node = $entity;
  }

  private function fix() {
    $this->fixDataType();
    $this->saveRawMetadata();
  }

  /**
   *
   */
  public function getModifiedDate() {
    $this->fix();
    return $this->node->getChangedTime();
  }

  /**
   *
   */
  public function getIdentifier() {
    $this->fix();

    return $this->node->uuid();
  }

  /**
   * The unaltered version of the metadata.
   */
  public function getRawMetadata() {
    $this->fix();
    if (isset($this->node->rawMetadata)) {
      return json_decode($this->node->rawMetadata);
    }
  }

  /**
   * Protected.
   */
  public function getDataType() {
    $this->fix();
    return $this->node->get('field_data_type')->value;
  }

  /**
   * Protected.
   */
  public function getMetaData() {
    $this->fix();
    return json_decode($this->node->get('field_json_metadata')->value);
  }

  /**
   * Protected.
   */
  public function setMetadata($metadata) {
    $this->fix();
    $this->node->set('field_json_metadata', json_encode($metadata));
  }

  /**
   *
   */
  public function setIdentifier($identifier) {
    $this->fix();
    $this->node->set('uuid', $identifier);
  }

  /**
   *
   */
  public function setTitle($title) {
    $this->fix();
    $this->node->set('title', $title);
  }

  public function isNew() {
    return $this->node->isNew();
  }

  /**
   * Private.
   */
  private function setDataType($type) {
    $this->fix();
    $this->node->set('field_data_type', $type);
  }

  /**
   * Private.
   */
  private function validate(EntityInterface $entity) {
    if (!($entity instanceof Node)) {
      throw new DataNodeLifeCycleEntityValidationException("We only work with nodes.");
    }

    if ($entity->bundle() != "data") {
      throw new DataNodeLifeCycleEntityValidationException("We only work with data nodes.");
    }
  }

  /**
   *
   */
  private function fixDataType() {
    if (empty($this->node->get('field_data_type')->value)) {
      $this->node->get('field_data_type')->value = 'dataset';
    }
  }

  /**
   *
   */
  private function saveRawMetadata() {
    // Temporarily save the raw json metadata, for later use.
    if (!isset($this->node->rawMetadata)) {
      $raw = $this->node->get('field_json_metadata')->value;
      $this->node->rawMetadata = $raw;
    }
  }

}
