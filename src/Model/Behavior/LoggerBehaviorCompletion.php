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
 * Code completion for LoggerBehavior
 *
 * @deprecated since 1.2.0, use @mixin annotation
 */
// @codingStandardsIgnoreStart
trait LoggerBehaviorCompletion// @codingStandardsIgnoreEnd
{
    /**
     * Set or get the scope
     *
     * @param mixed $args if $args = false then reset the scope
     * @return Table|array
     * @deprecated 1.2.0 use setLogScope()/getLogScope() instead.
     */
    public function logScope($args = null)
    {
        return $this->__call('logScope', func_get_args());
    }

    /**
     * Set or get the log issuer
     *
     * @param Entity $issuer the issuer
     * @return Table
     * @deprecated 1.2.0 use setLogIssuer()/getLogIssuer() instead.
     */
    public function logIssuer(Entity $issuer = null)
    {
        return $this->__call('logIssuer', func_get_args());
    }

    /**
     * Set or get the log message builder
     *
     * @param callable $handler the message build method
     * @return callable|void
     * @deprecated 1.2.0 use setLogMessageBuilder()/getLogMessageBuilder() instead.
     */
    public function logMessageBuilder(callable $handler = null)
    {
        return $this->__call('logMessageBuilder', func_get_args());
    }

    /**
     * Record a custom log
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
