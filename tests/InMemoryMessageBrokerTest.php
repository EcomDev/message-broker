<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\MessageBroker;

use PHPUnit\Framework\TestCase;

class InMemoryMessageBrokerTest extends TestCase
{
    /** @var InMemoryMessageBroker */
    private $messageBroker;

    /**
     * @var SequenceRecorder
     */
    private $sequenceRecorder;

    protected function setUp()
    {
        $this->messageBroker = InMemoryMessageBroker::createWithoutMessageLimit();
        $this->sequenceRecorder = new SequenceRecorder();
    }

    /** @test */
    public function messagesAreDeliveredToConsumer()
    {
        $this->messageBroker->addConsumer('Consumer #1', $this->sequenceRecorder->addFirstArgumentToSequence());

        $this->messageBroker->sendMessage(
            ['my' => 'Message #1'],
            $this->sequenceRecorder->addTextToSequence('Message #1 has been received'),
            $this->sequenceRecorder->addFirstArgumentToSequence()
        );

        $this->messageBroker->sendMessage(
            ['my' => 'Message #2'],
            $this->sequenceRecorder->addTextToSequence('Message #2 has been received'),
            $this->sequenceRecorder->addFirstArgumentToSequence()
        );

        $this->verifyActionSequence(
            ['my' => 'Message #1'],
            'Message #1 has been received',
            ['my' => 'Message #2'],
            'Message #2 has been received'
        );
    }

    /** @test */
    public function returnsMessageBackWhenNoConsumerIsAdded()
    {
        $this->messageBroker->sendMessage(
            ['hello' => 'world'],
            $this->sequenceRecorder->addTextToSequence('Message success callback, that should not be called'),
            $this->sequenceRecorder->addFirstArgumentToSequence()
        );

        $this->verifyActionSequence(new RouteNotFoundException());
    }

    /** @test */
    public function returnsMessageBackWhenConsumerIsRemovedBeforeRouting()
    {
        $this->messageBroker->addConsumer('Consumer #1', $this->sequenceRecorder->addFirstArgumentToSequence());

        $this->messageBroker->sendMessage(
            ['hello' => 'world'],
            $this->sequenceRecorder->addTextToSequence('Message success callback, that should not be called'),
            $this->sequenceRecorder->addFirstArgumentToSequence()
        );

        $this->messageBroker->removeConsumer('Consumer #1');

        $this->verifyActionSequence(new RouteNotFoundException());
    }

    /** @test */
    public function returnsMessageBackWhenConsumerRejectsMessage()
    {
        $this->messageBroker->addConsumer('Consumer #1', function () {
            return false;
        });

        $this->messageBroker->sendMessage(
            ['hello' => 'world'],
            $this->sequenceRecorder->addTextToSequence('Message success callback, that should not be called'),
            $this->sequenceRecorder->addFirstArgumentToSequence()
        );

        $this->verifyActionSequence(new RouteNotFoundException());
    }

    /** @test */
    public function sendsHeadersTogetherWithMessageToConsumer()
    {
        $this->messageBroker->addConsumer('Consumer #1', $this->sequenceRecorder->addAllArgumentsToSequence());

        $this->messageBroker->sendMessage(
            ['my' => 'Message #1'],
            $this->sequenceRecorder->addTextToSequence('Message #1 is received'),
            $this->sequenceRecorder->addFirstArgumentToSequence(),
            ['header' => 'value']
        );

        $this->verifyActionSequence(
            [['my' => 'Message #1'], ['header' => 'value']],
            'Message #1 is received'
        );
    }

    /** @test */
    public function sendsMessageToFirstConsumerThatAcceptsIt()
    {
        $this->messageBroker->addConsumer('Consumer #1', function () {
            return false;
        });

        $this->messageBroker->addConsumer('Consumer #2', $this->sequenceRecorder->addFirstArgumentToSequence());
        $this->messageBroker->addConsumer(
            'Consumer #3',
            $this->sequenceRecorder->addTextToSequence('Consumer #3 should not be called')
        );

        $this->messageBroker->sendMessage(
            'My Message #1',
            $this->sequenceRecorder->addTextToSequence('Message has been accepted'),
            $this->sequenceRecorder->addFirstArgumentToSequence()
        );

        $this->verifyActionSequence('My Message #1', 'Message has been accepted');
    }

    /** @test */
    public function prioritizesMessageToAConsumerWithFilterForItsHeader()
    {
        $this->messageBroker->addConsumer(
            'Not Filtered',
            $this->sequenceRecorder->addTextToSequence('Consumer should not be called')
        );

        $this->messageBroker->addConsumerWithHeaderFilter(
            'Consumer with filter',
            $this->sequenceRecorder->addAllArgumentsToSequence(),
            ['type' => 'filter']
        );

        $this->messageBroker->sendMessage(
            'Filtered Message',
            $this->sequenceRecorder->addTextToSequence('Filtered Message is delivered'),
            $this->sequenceRecorder->addFirstArgumentToSequence(),
            ['type' => 'filter']
        );

        $this->verifyActionSequence(['Filtered Message', ['type' => 'filter']], 'Filtered Message is delivered');
    }


    /** @test */
    public function skipsFilteredConsumerIfMessageDoesNotMatch()
    {
        $this->messageBroker->addConsumer('Not Filtered', $this->sequenceRecorder->addFirstArgumentToSequence());
        $this->messageBroker->addConsumerWithHeaderFilter(
            'Consumer with filter',
            $this->sequenceRecorder->addTextToSequence('This filter should not be triggered'),
            ['type' => 'filter']
        );

        $this->messageBroker->sendMessage(
            'Filtered Message',
            $this->sequenceRecorder->addTextToSequence('Filtered Message is delivered'),
            $this->sequenceRecorder->addFirstArgumentToSequence(),
            ['type' => 'filter2']
        );

        $this->verifyActionSequence('Filtered Message', 'Filtered Message is delivered');
    }

    /** @test */
    public function returnsMessageBackWhenFilteredConsumerIsRemovedBeforeRouting()
    {
        $this->messageBroker->addConsumerWithHeaderFilter(
            'Filtered Consumer #1',
            $this->sequenceRecorder->addFirstArgumentToSequence(),
            ['type' => 'filter']
        );

        $this->messageBroker->sendMessage(
            ['hello' => 'world'],
            $this->sequenceRecorder->addTextToSequence('Message success callback, that should not be called'),
            $this->sequenceRecorder->addFirstArgumentToSequence(),
            ['type' => 'filter']
        );

        $this->messageBroker->removeConsumer('Filtered Consumer #1');

        $this->verifyActionSequence(new RouteNotFoundException());
    }

    /** @test */
    public function allowsPartialHeaderMessageMatchForConsumer()
    {
        $this->messageBroker->addConsumerWithHeaderFilter(
            'Matched Consumer',
            $this->sequenceRecorder->addFirstArgumentToSequence(),
            ['type' => 'filter']
        );

        $this->messageBroker->sendMessage(
            'Filtered Message',
            $this->sequenceRecorder->addTextToSequence('Filtered Message is delivered'),
            $this->sequenceRecorder->addFirstArgumentToSequence(),
            ['header' => 'name', 'type' => 'filter']
        );

        $this->verifyActionSequence('Filtered Message', 'Filtered Message is delivered');
    }

    /** @test */
    public function allowsMultipleHeaderMatchForFilteredConsumer()
    {
        $this->messageBroker->addConsumerWithHeaderFilter(
            'Matched Consumer',
            $this->sequenceRecorder->addFirstArgumentToSequence(),
            ['type' => 'filter', 'reply-to' => 'message-id']
        );

        $this->messageBroker->sendMessage(
            'Message should not be delivered',
            $this->sequenceRecorder->addTextToSequence('It is delivered, but should not'),
            $this->sequenceRecorder->addFirstArgumentToSequence(),
            ['type' => 'filter']
        );

        $this->messageBroker->sendMessage(
            'Reply Filtered Message',
            $this->sequenceRecorder->addTextToSequence('Reply Filtered Message is delivered'),
            $this->sequenceRecorder->addFirstArgumentToSequence(),
            ['type' => 'filter', 'reply-to' => 'message-id']
        );

        $this->verifyActionSequence(
            new RouteNotFoundException(),
            'Reply Filtered Message',
            'Reply Filtered Message is delivered'
        );
    }

    /** @test */
    public function allowsNotOrderedHeaderMatchForFilteredConsumer()
    {
        $this->messageBroker->addConsumerWithHeaderFilter(
            'Matched Consumer',
            $this->sequenceRecorder->addFirstArgumentToSequence(),
            ['type' => 'filter', 'reply-to' => 'message-id']
        );

        $this->messageBroker->sendMessage(
            'Reply Filtered Message',
            $this->sequenceRecorder->addTextToSequence('Reply Filtered Message is delivered'),
            $this->sequenceRecorder->addFirstArgumentToSequence(),
            ['reply-to' => 'message-id', 'type' => 'filter']
        );

        $this->verifyActionSequence(
            'Reply Filtered Message',
            'Reply Filtered Message is delivered'
        );
    }

    /** @test */
    public function deliversAllMessagesInSingleRoutingByDefault()
    {
        $this->messageBroker->addConsumer('Consumer', $this->sequenceRecorder->addFirstArgumentToSequence());

        $messageIndexes = range(0, 100);

        $expectedSequence = [];
        $emptyCallback = function () {
        };

        foreach ($messageIndexes as $index) {
            $message = sprintf('Message #%d', $index);
            $expectedSequence[] = $message;

            $this->messageBroker->sendMessage($message, $emptyCallback, $emptyCallback);
        }

        $this->verifyActionSequence(...$expectedSequence);
    }

    /** @test */
    public function deliversLimitedNumberOfMessagesPerRoutingWhenLimitIsSet()
    {
        $this->messageBroker = InMemoryMessageBroker::createWithMessageLimit(2);
        $this->messageBroker->addConsumer('Consumer', $this->sequenceRecorder->addFirstArgumentToSequence());

        $emptyCallback = function () {
            // Do nothing
        };

        $this->messageBroker->sendMessage('Message #1', $emptyCallback, $emptyCallback);
        $this->messageBroker->sendMessage('Message #2', $emptyCallback, $emptyCallback);
        $this->messageBroker->sendMessage('Message #3', $emptyCallback, $emptyCallback);
        $this->messageBroker->sendMessage('Message #4', $emptyCallback, $emptyCallback);
        $this->messageBroker->sendMessage('Message #5', $emptyCallback, $emptyCallback);

        $this->verifyActionSequence('Message #1', 'Message #2');
        $this->verifyActionSequence('Message #1', 'Message #2', 'Message #3', 'Message #4');
        $this->verifyActionSequence('Message #1', 'Message #2', 'Message #3', 'Message #4', 'Message #5');
    }

    private function verifyActionSequence(...$actions): void
    {
        $this->messageBroker->routeMessages();
        $this->sequenceRecorder->assertSequence(...$actions);
    }
}
