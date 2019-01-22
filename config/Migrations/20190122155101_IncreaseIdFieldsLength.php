<?php
/**
 * Copyright 2019 ELASTIC Consultants Inc.
 */

use Migrations\AbstractMigration;

class IncreaseIdFieldsLength extends AbstractMigration
{

    public function up()
    {
        $table = $this->table('activity_logs', ['id' => false, 'collation' => 'utf8_general_ci']);
        $table
            ->changeColumn('scope_id', 'string', [
                'default' => null,
                'limit'   => 64,
                'null'    => false,
            ])
            ->changeColumn('issuer_id', 'string', [
                'default' => null,
                'limit'   => 64,
                'null'    => true,
            ])
            ->changeColumn('object_id', 'string', [
                'default' => null,
                'limit'   => 64,
                'null'    => true,
            ]);
        $table->update();
    }

    public function down()
    {
        $table = $this->table('activity_logs', ['id' => false, 'collation' => 'utf8_general_ci']);
        $table
            ->changeColumn('scope_id', 'string', [
                'default' => null,
                'limit'   => 32,
                'null'    => false,
            ])
            ->changeColumn('issuer_id', 'string', [
                'default' => null,
                'limit'   => 32,
                'null'    => true,
            ])
            ->changeColumn('object_id', 'string', [
                'default' => null,
                'limit'   => 32,
                'null'    => true,
            ]);
        $table->update();
    }
}
