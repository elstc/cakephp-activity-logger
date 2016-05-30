<?php

/**
 *
 * Copyright 2016 ELASTIC Consultants Inc.
 *
 */
use Cake\Database\Type;

if (!Type::map('json_data')) {
    Type::map('json_data', '\Elastic\ActivityLogger\Database\Type\JsonDataType');
}
