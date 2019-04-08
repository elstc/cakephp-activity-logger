<?php

/**
 *
 * Copyright 2016 ELASTIC Consultants Inc.
 *
 */
use Cake\Database\Type;
use Elastic\ActivityLogger\Database\Type\JsonDataType;

// TODO: Drop JsonDataType next release
if (method_exists(Type::class, 'getMap')) {
    if (!Type::getMap('json_data')) {
        Type::setMap(array_merge(Type::getMap(), ['json_data' => JsonDataType::class]));
    }
} elseif (!Type::map('json_data')) {
    Type::map('json_data', JsonDataType::class);
}
