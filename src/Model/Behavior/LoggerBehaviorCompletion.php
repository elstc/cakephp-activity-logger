<?php

/*
 *
 * Copyright 2016 ELASTIC Consultants Inc.
 *
 */

namespace Elastic\ActivityLogger\Model\Behavior;

/**
 * LoggerBehaviorのメソッド補完
 */
trait LoggerBehaviorCompletion
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
     * @param \Cake\ORM\Entity $issuer
     * @return Table
     */
    public function logIssuer(\Cake\ORM\Entity $issuer = null)
    {
        return $this->__call('logIssuer', func_get_args());
    }

    /**
     * カスタムログの記述
     *
     * @param string $level
     * @param string $message
     * @param array $context
     */
    public function activityLog($level, $message, array $context = [])
    {
        return $this->__call('activityLog', func_get_args());
    }
}
