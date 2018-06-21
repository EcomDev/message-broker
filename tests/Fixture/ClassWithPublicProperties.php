<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\MessageBroker\Fixture;

final class ClassWithPublicProperties
{
    public $propertyOne;
    public $propertyTwo;
    public $propertyThree;

    public function __construct($propertyOne, $propertyTwo, $propertyThree)
    {
        $this->propertyOne = $propertyOne;
        $this->propertyTwo = $propertyTwo;
        $this->propertyThree = $propertyThree;
    }
}
