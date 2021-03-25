<?php
/** @noinspection PhpUnused */

/**
 * Copyright 2019 ELASTIC Consultants Inc.
 */

use Migrations\AbstractMigration;

class IncreaseModelFieldsLength extends AbstractMigration
{

    public function up()
    {
        $table = $this->table('activity_logs', ['id' => false, 'collation' => 'utf8_general_ci']);
        $table
            ->changeColumn('scope_model', 'string', [
                'default' => null,
                'limit'   => 128,
                'null'    => false,
            ])
            ->changeColumn('issuer_model', 'string', [
                'default' => null,
                'limit'   => 128,
                'null'    => true,
            ])
            ->changeColumn('object_model', 'string', [
                'default' => null,
                'limit'   => 128,
                'null'    => true,
            ]);
        $table->update();
    }

    public function down()
    {
        $table = $this->table('activity_logs', ['id' => false, 'collation' => 'utf8_general_ci']);
        $table
            ->changeColumn('scope_model', 'string', [
                'default' => null,
                'limit'   => 64,
                'null'    => false,
            ])
            ->changeColumn('issuer_model', 'string', [
                'default' => null,
                'limit'   => 64,
                'null'    => true,
            ])
            ->changeColumn('object_model', 'string', [
                'default' => null,
                'limit'   => 64,
                'null'    => true,
            ]);
        $table->update();
    }
}
