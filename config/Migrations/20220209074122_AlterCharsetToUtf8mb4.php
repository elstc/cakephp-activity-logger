<?php
/*
 * Copyright 2022 ELASTIC Consultants Inc.
 */

use Cake\Utility\Hash;
use Migrations\AbstractMigration;

/**
 * 20220209074122 テーブル、カラムの文字コードをutf8mb4に変更する
 *
 * @codingStandardsIgnoreStart
 */
class AlterCharsetToUtf8mb4 extends AbstractMigration//@codingStandardsIgnoreEnd
{
    /**
     * Up Method.
     *
     * @return void
     */
    public function up()
    {
        $schemaCollection = $this->getQueryBuilder('select')->getConnection()->getSchemaCollection();

        if ($schemaCollection === null) {
            throw new Exception('$schemaCollection not exists.');
        }

        $this->execute('SET FOREIGN_KEY_CHECKS=0;');

        $tableNames = $schemaCollection->listTables();
        $tableNames = array_filter($tableNames, static function ($tableName) {
            return in_array($tableName, ['activity_logs'], true)
                && !preg_match('/_phinxlog$/', $tableName);
        });
        foreach ($tableNames as $tableName) {
            // テーブルの文字照合順変更
            $tableSchema = $schemaCollection->describe($tableName);
            $tableCollation = Hash::get($tableSchema->getOptions(), 'collation', '');
            if (preg_match('/\Autf8_/', $tableCollation)) {
                $this->execute(sprintf('ALTER TABLE `%s` CHARACTER SET %s COLLATE %s', $tableName, 'utf8mb4', 'utf8mb4_general_ci'));
            }

            // カラムの文字照合順序変更
            $table = $this->table($tableName);
            $colNames = $tableSchema->columns();
            foreach ($colNames as $colName) {
                $column = $tableSchema->getColumn($colName);
                $columnCollation = Hash::get($column, 'collate', '');
                if (preg_match('/\Autf8_/', $columnCollation)) {
                    $colType = $column['type'];
                    $colOpts = $column;
                    unset($colOpts['type'], $colOpts['collate'], $colOpts['fixed']);
                    $table->changeColumn($colName, $colType, ['collation' => 'utf8mb4_general_ci', 'encoding' => 'utf8mb4'] + $colOpts);
                }
            }
            $table->update();
        }

        $this->execute('SET FOREIGN_KEY_CHECKS=1;');
    }
}
