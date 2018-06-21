<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\MessageBroker;

use PHPUnit\Framework\Assert;

class SequenceRecorder
{
    private $actionSequence = [];

    public function assertSequence(...$actions): void
    {
        Assert::assertEquals($actions, $this->actionSequence);
    }

    public function addAllArgumentsToSequence(): callable
    {
        return function () {
            $this->actionSequence[] = func_get_args();
        };
    }

    public function addFirstArgumentToSequence(): callable
    {
        return function () {
            $this->actionSequence[] = func_get_arg(0);
        };
    }

    public function addTextToSequence(string $text): callable
    {
        return function () use ($text) {
            $this->actionSequence[] = $text;
        };
    }
}
