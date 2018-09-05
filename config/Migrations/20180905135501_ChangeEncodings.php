<?php

use Migrations\AbstractMigration;

class ChangeEncodings extends AbstractMigration
{

    public function up()
    {
        $table = $this->table('activity_logs', ['id' => false, 'collation' => 'utf8_general_ci']);
        $table
            ->changeColumn('message', 'text', [
                'collation' => 'utf8_general_ci',
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->changeColumn('data', 'text', [
                'collation' => 'utf8_general_ci',
                'comment' => 'json encoded data',
                'default' => null,
                'limit' => null,
                'null' => true,
            ]);
        $table->update();
    }

    public function down()
    {

    }
}
