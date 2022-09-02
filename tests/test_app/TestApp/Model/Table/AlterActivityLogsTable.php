<?php
declare(strict_types=1);

namespace TestApp\Model\Table;

use Elastic\ActivityLogger\Model\Entity\ActivityLog;
use Elastic\ActivityLogger\Model\Table\ActivityLogsTable;

class AlterActivityLogsTable extends ActivityLogsTable
{
    public function initialize(array $config): void
    {
        $this->setTable('activity_logs');
        $this->setEntityClass(ActivityLog::class);
    }
}
