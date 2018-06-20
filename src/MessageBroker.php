<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\MessageBroker;

/**
 * Message Broker
 *
 * Main contract of
 *
 */
interface MessageBroker
{
    /**
     * Writes message for routing
     *
     * When message is delivered and accepted by a consumer,
     * **$onSuccess** callable MUST be invoked.
     *
     * When message is not delivered or not accepted by a consumer,
     * **$onFailure** callable MUST be invoked with appropriate exception as an argument.
     */
    public function sendMessage($message, callable $onSuccess, callable $onFailure, array $headers = []): void;

    /**
     * Adds message consumer
     *
     * A **$consumer** callable CAN have the following signature:
     * ```php
     * function ($message, array $headers) {}
     * ```
     * Message is considered accepted by a consumer, unless it implicitly returns `false`
     *
     * Consumer identifier MUST be unique. When identifier is not unique, behaviour is **undefined**.
     */
    public function addConsumer(string $consumerId, callable $consumer): void;

    /**
     * Adds message consumer with filter
     *
     * Messages are submitted to a consumer only when headers match registered condition.
     *
     * Condition MAY be multiple header name/value pairs.
     * Order of name/value pairs CAN be different from the order in the message.
     */
    public function addConsumerWithHeaderFilter(string $consumerId, callable $consumer, array $condition): void;

    /**
     * Removes consumer
     *
     * Consumer MAY still be invoked after removal before next routing cycle.
     * It is recommended to take appropriate actions on received messages or reject it.
     */
    public function removeConsumer(string $consumerId): void;
}
