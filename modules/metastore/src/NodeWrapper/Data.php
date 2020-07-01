<?php

namespace Drupal\metastore\NodeWrapper;

use Drupal\common\Exception\DataNodeLifeCycleEntityValidationException;
use Drupal\Core\Entity\EntityInterface;
use Drupal\node\Entity\Node;

/**
 *
 */
class Data {

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

    $this->fixDataType();
    $this->saveRawMetadata();
  }

  /**
   *
   */
  public function getModifiedDate() {
    return $this->node->getChangedTime();
  }

  /**
   *
   */
  public function getIdentifier() {
    return $this->node->uuid();
  }

  /**
   * The unaltered version of the metadata.
   */
  public function getRawMetadata() {
    if ($this->node->rawMetadata) {
      return json_decode($this->node->rawMetadata);
    }
  }

  /**
   * Protected.
   */
  public function getDataType() {
    return $this->node->get('field_data_type')->value;
  }

  /**
   * Protected.
   */
  public function getMetaData() {
    /* @var $entity \Drupal\node\Entity\Node */
    $entity = $this->node;
    return json_decode($entity->get('field_json_metadata')->value);
  }

  /**
   * Protected.
   */
  public function setMetadata($metadata) {
    /* @var $entity \Drupal\node\Entity\Node */
    $entity = $this->node;
    $entity->set('field_json_metadata', json_encode($metadata));
  }

  /**
   *
   */
  public function setIdentifier($identifier) {
    $this->node->set('uuid', $identifier);
  }

  /**
   *
   */
  public function setTitle($title) {
    $this->node->set('title', $title);
  }

  /**
   * Private.
   */
  private function setDataType($type) {
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
    if (empty($this->getDataType())) {
      $this->setDataType('dataset');
    }
  }

  /**
   *
   */
  private function saveRawMetadata() {
    // Temporarily save the raw json metadata, for later use.
    $this->node->rawMetadata = json_encode($this->getMetaData());
  }

}
