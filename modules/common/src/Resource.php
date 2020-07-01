<?php

namespace Drupal\common;

use Procrastinator\HydratableTrait;
use Procrastinator\JsonSerializeTrait;

/**
 * Class Resource.
 *
 * @todo Move the upstream datastore resource somwhere reusable and make it
 * use the procrastinator traits for serialization and hydration.
 */
class Resource implements \JsonSerializable {
  use HydratableTrait, JsonSerializeTrait;

  private $filePath;
  private $identifier;
  private $mimeType;
  private $perspective;
  private $version;

  /**
   *
   */
  public function __construct($file_path, $mime_type, $perspective = 'source') {
    $this->identifier = md5($file_path);
    $this->filePath = $file_path;
    $this->mimeType = $mime_type;
    $this->perspective = $perspective;
    $this->version = time();
  }

  /**
   *
   */
  public function createNewVersion() {
    $newVersion = time();
    if ($newVersion == $this->version) {
      $newVersion++;
    }

    return $this->createCommon('version', $newVersion);
  }

  /**
   *
   */
  public function createNewPerspective($perspective, $path) {
    $new = $this->createCommon('perspective', $perspective);
    $new->changeFilePath($path);
    return $new;
  }

  /**
   *
   */
  public function changeFilePath($newPath) {
    $this->filePath = $newPath;
  }

  /**
   *
   */
  private function createCommon($property, $value) {
    $current = $this->{$property};
    $new = $value;
    $this->{$property} = $new;
    $newResource = clone $this;
    $this->{$property} = $current;
    return $newResource;
  }

  /**
   * Getter.
   */
  public function getIdentifier() {
    return $this->identifier;
  }

  /**
   * Getter.
   */
  public function getFilePath() {
    return $this->filePath;
  }

  /**
   * Getter.
   */
  public function getMimeType() {
    return $this->mimeType;
  }

  /**
   *
   */
  public function getVersion() {
    return $this->version;
  }

  /**
   *
   */
  public function getPerspective() {
    return $this->perspective;
  }

  /**
   *
   */
  public function getUniqueIdentifier() {
    return "{$this->identifier}__{$this->version}__{$this->perspective}";
  }

  /**
   *
   */
  public function jsonSerialize() {
    return $this->serialize();
  }

  /**
   *
   */
  public static function parseUniqueIdentifier(string $uid): array {
    $pieces = explode("__", $uid);
    if (count($pieces) != 3) {
      throw new \Exception("Badly constructed unique identifier {$uid}");
    }
    return ['identifier' => $pieces[0], 'version' => $pieces[1], 'perspective' => $pieces[2]];

  }

}
