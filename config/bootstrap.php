<?php

/**
 *
 * Copyright 2016 ELASTIC Consultants Inc.
 *
 */
use Cake\Database\Type;
use Elastic\ActivityLogger\Database\Type\JsonDataType;

// TODO: Drop JsonDataType next release
$getMap = method_exists(Type::class, 'getMap') ? 'getMap' : 'map';
if (!Type::$getMap('json_data')) {
    Type::map('json_data', JsonDataType::class);
}
