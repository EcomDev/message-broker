<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\MessageBroker\Fixture;

final class ClassWithDefaultPropertyValues
{
    private $propertyOne;
    private $propertyTwo = 'default_value_two';
    private $propertyThree = 'default_value_three';

    public function __construct($propertyOne, $propertyTwo = null, $propertyThree = null)
    {
        $this->propertyOne = $propertyOne;
        $this->propertyTwo = $propertyTwo ?? $this->propertyTwo;
        $this->propertyThree = $propertyThree ?? $this->propertyThree;
    }
}
