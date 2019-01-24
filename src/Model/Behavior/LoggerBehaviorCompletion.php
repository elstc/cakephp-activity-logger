<?php

/*
 *
 * Copyright 2016 ELASTIC Consultants Inc.
 *
 */

namespace Elastic\ActivityLogger\Model\Behavior;

use Cake\ORM\Entity;
use Cake\ORM\Table;
use Elastic\ActivityLogger\Model\Entity\ActivityLog;

/**
 * LoggerBehaviorのメソッド補完
 *
 * @deprecated since 1.2.0, use @mixin annotation
 */
// @codingStandardsIgnoreStart
trait LoggerBehaviorCompletion// @codingStandardsIgnoreEnd
{
    /**
     * ログスコープの設定
     *
     * @param mixed $args if $args === false リセット
     * @return Table
     */
    public function logScope($args = null)
    {
        return $this->__call('logScope', func_get_args());
    }

    /**
     * ログ発行者の設定
     *
     * @param Entity $issuer the log issuer
     * @return Table
     */
    public function logIssuer(Entity $issuer = null)
    {
        return $this->__call('logIssuer', func_get_args());
    }

    /**
     * メッセージ生成メソッドの設定
     *
     * @param callable $handler メッセージ生成メソッド
     * @return callable
     */
    public function logMessageBuilder(callable $handler = null)
    {
        return $this->__call('logMessageBuilder', func_get_args());
    }

    /**
     * カスタムログの記述
     *
     * @param string $level log level
     * @param string $message log message
     * @param array $context context data
     * [
     *   'object' => Entity,
     *   'issuer' => Entity,
     *   'scope' => Entity[],
     *   'action' => string,
     *   'data' => array,
     * ]
     * @return ActivityLog[]|array
     */
    public function activityLog($level, $message, array $context = [])
    {
        return $this->__call('activityLog', func_get_args());
    }
}
