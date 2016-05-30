<?php

namespace Elastic\ActivityLogger\Database\Type;

use Cake\Database\Driver;
use Cake\Database\Type;
use PDO;

/**
 * @see http://book.cakephp.org/3.0/en/orm/database-basics.html#adding-custom-database-types
 */
class JsonDataType extends Type
{

    public function toPHP($value, Driver $driver)
    {
        if ($value === null) {
            return null;
        }
        return json_decode($value, true);
    }

    public function marshal($value)
    {
        if (is_array($value) || $value === null) {
            return $value;
        }
        return json_decode($value, true);
    }

    public function toDatabase($value, Driver $driver)
    {
        return json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    public function toStatement($value, Driver $driver)
    {
        if ($value === null) {
            return PDO::PARAM_NULL;
        }
        return PDO::PARAM_STR;
    }

}
