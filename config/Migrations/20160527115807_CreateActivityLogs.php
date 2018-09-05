<?php

use Migrations\AbstractMigration;

class CreateActivityLogs extends AbstractMigration
{

    public function change()
    {
        $table = $this->table('activity_logs', ['id' => false, 'collation' => 'utf8_general_ci'])
            ->addColumn('id', 'biginteger', [
                'autoIncrement' => true,
                'limit'         => 20,
                'null'          => false,
                'signed'        => false,
            ])
            ->addPrimaryKey(['id']);
        $table->addColumn('created_at', 'timestamp', [
            'default' => 'CURRENT_TIMESTAMP',
            'limit'   => null,
            'null'    => false,
        ]);

        $table->addColumn('scope_model', 'string', [
                'default' => null,
                'limit'   => 64,
                'null'    => false,
            ])
            ->addColumn('scope_id', 'string', [
                'default' => null,
                'limit'   => 36,
                'null'    => false,
            ])
            ->addColumn('issuer_model', 'string', [
                'default' => null,
                'limit'   => 64,
                'null'    => true,
            ])
            ->addColumn('issuer_id', 'string', [
                'default' => null,
                'limit'   => 36,
                'null'    => true,
            ])
            ->addColumn('object_model', 'string', [
                'default' => null,
                'limit'   => 64,
                'null'    => true,
            ])
            ->addColumn('object_id', 'string', [
                'default' => null,
                'limit'   => 36,
                'null'    => true,
            ])
            ->addColumn('level', 'string', [
                'comment' => 'ログレベル',
                'default' => null,
                'limit'   => 16,
                'null'    => false,
            ])
            ->addColumn('action', 'string', [
                'default' => null,
                'limit'   => 64,
                'null'    => true,
            ])
            ->addColumn('message', 'text', [
                'default' => null,
                'limit'   => null,
                'null'    => true,
            ])
            ->addColumn('data', 'text', [
                'comment' => 'json encoded data',
                'default' => null,
                'limit'   => null,
                'null'    => true,
            ])
            ->addIndex([
                'scope_model',
                'scope_id',
                ], [
                'name'   => 'IX_scope',
                'unique' => false,
            ])
            ->addIndex([
                'issuer_model',
                'issuer_id',
                ], [
                'name'   => 'IX_issuer',
                'unique' => false,
            ])
            ->addIndex([
                'object_model',
                'object_id',
                ], [
                'name'   => 'IX_object',
                'unique' => false,
            ])
            ->addIndex([
                'level',
                ], [
                'name'   => 'IX_level',
                'unique' => false,
            ])
            ->addIndex([
                'action',
                ], [
                'name'   => 'IX_action',
                'unique' => false,
            ])
            ->create();
    }

    public function down()
    {

    }
}
