<?php
declare(strict_types=1);

namespace Elastic\ActivityLogger\Model\Table;

use Cake\Datasource\EntityInterface;
use Cake\ORM\Table;

/**
 * ActivityLogs Table Interface
 */
interface ActivityLogsTableInterface
{
    /**
     * Build parameter from an entity
     *
     * @param \Cake\Datasource\EntityInterface|null $object an entity
     * @return array [object_model, object_id]
     */
    public function buildObjectParameter(?EntityInterface $object): array;

    /**
     * Get scope's ID
     *
     * if composite primary key, it will return concatenate values
     *
     * @param \Cake\ORM\Table $table target table
     * @param \Cake\Datasource\EntityInterface $entity an entity
     * @return string|int
     */
    public function getScopeId(Table $table, EntityInterface $entity): string|int;
}
