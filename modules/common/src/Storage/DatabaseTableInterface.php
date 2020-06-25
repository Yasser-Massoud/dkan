<?php

namespace Drupal\common\Storage;

use Contracts\RemoverInterface;
use Contracts\RetrieverInterface;
use Contracts\StorerInterface;
use Dkan\Datastore\Storage\StorageInterface;

interface DatabaseTableInterface extends StorageInterface, StorerInterface, RetrieverInterface, RemoverInterface{
  public function destroy();
  public function query(Query $query);
}
