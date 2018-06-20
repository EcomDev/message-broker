<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\MessageBroker;

/**
 * Simple message broker implementation
 */
final class InMemoryMessageBroker implements MessageBroker
{
    private const NO_ROUTING_LIMIT = 0;

    /**
     * Queue of messages
     *
     * @var \SplQueue
     */
    private $messageQueue;

    /**
     * Receivers of the messages
     *
     * @var array
     */
    private $consumers = [];

    /**
     * Ordered list of consumers
     *
     * @var string[]
     */
    private $consumerList = [];

    /**
     * Filter conditions to consumer
     *
     * @var array
     */
    private $filterConditions = [];

    /**
     * Ordered list of items
     *
     * @var string[]
     */
    private $filteredConsumerList = [];

    /**
     * Flag for verifying if consumer list needs to be cleaned
     *
     * @var bool
     */
    private $rebuildConsumersFlag = false;

    /**
     * @var int
     */
    private $routingLimit;

    /**
     * Configures broker with limit of messages per each message routing
     */
    private function __construct(int $routingLimit)
    {
        $this->messageQueue = new \SplQueue();
        $this->routingLimit = $routingLimit;
    }

    /**
     * Creates an instance without limiting on messages per routing
     */
    public static function createWithoutMessageLimit(): InMemoryMessageBroker
    {
        return new self(self::NO_ROUTING_LIMIT);
    }

    /**
     * Creates an instance with limit on number of messages being processed per routing
     */
    public static function createWithMessageLimit(int $limit): InMemoryMessageBroker
    {
        return new self($limit);
    }

    /** {@inheritdoc} */
    public function sendMessage($message, callable $onSuccess, callable $onFailure, array $headers = []): void
    {
        $this->messageQueue->enqueue([$message, $onSuccess, $onFailure, $headers]);
    }

    /** {@inheritdoc} */
    public function addConsumer(string $consumerId, callable $consumer): void
    {
        $this->consumers[$consumerId] = $consumer;
        $this->consumerList[] = $consumerId;
    }

    /** {@inheritdoc} */
    public function addConsumerWithHeaderFilter(string $consumerId, callable $consumer, array $condition): void
    {
        $this->consumers[$consumerId] = $consumer;
        $this->filterConditions[$consumerId] = $condition;
        $this->filteredConsumerList[] = $consumerId;
    }

    /** {@inheritdoc} */
    public function removeConsumer(string $consumerId): void
    {
        unset($this->consumers[$consumerId]);
        $this->rebuildConsumersFlag = true;
    }

    /**
     * Routes messages to consumers
     *
     * This method is designed to deliver messages
     * when async application is idle.
     *
     * It is NOT recommended to call it right away after the message has been sent
     */
    public function routeMessages(): void
    {
        $this->rebuildConsumerLists();

        $routedMessages = 0;
        while (!$this->isMessageRoutingLimitReached($routedMessages)) {
            $this->routeMessage(...$this->messageQueue->dequeue());
            $routedMessages++;
        }
    }

    private function routeMessage($message, callable $onSuccess, callable $onFailure, array $headers): void
    {
        $possibleConsumers = $this->createMessageRoute($headers);

        foreach ($possibleConsumers as $consumerId) {
            $consumer = $this->consumers[$consumerId];
            if ($consumer($message, $headers) !== false) {
                $onSuccess();
                return;
            }
        }

        $onFailure(new RouteNotFoundException());
    }

    private function rebuildConsumerLists()
    {
        if (!$this->rebuildConsumersFlag) {
            return;
        }

        $this->rebuildConsumersFlag = false;
        $consumerExists = function ($consumerId) {
            return isset($this->consumers[$consumerId]);
        };

        $this->consumerList = array_filter($this->consumerList, $consumerExists);
        $this->filteredConsumerList = array_filter($this->filteredConsumerList, $consumerExists);
    }

    private function prioritizeFilteredConsumers(array $headers): \Traversable
    {
        foreach ($this->filteredConsumerList as $consumerId) {
            $condition = $this->filterConditions[$consumerId];
            $match = array_intersect_assoc($headers, $condition);
            if ($match && count($match) === count($condition)) {
                yield $consumerId;
            }
        }
    }

    private function createMessageRoute(array $headers): \Traversable
    {
        foreach ($this->prioritizeFilteredConsumers($headers) as $consumerId) {
            yield $consumerId;
        }

        foreach ($this->consumerList as $consumerId) {
            yield $consumerId;
        }
    }

    private function isMessageRoutingLimitReached(int $routedMessages): bool
    {
        return $this->messageQueue->isEmpty()
            || ($this->routingLimit > self::NO_ROUTING_LIMIT && $routedMessages === $this->routingLimit);
    }
}
