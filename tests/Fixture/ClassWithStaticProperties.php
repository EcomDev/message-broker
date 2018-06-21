<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\MessageBroker\Fixture;

final class ClassWithStaticProperties
{
    private $propertyOne;
    private $propertyTwo;
    private $propertyThree;

    public static $staticProperty = 'staticPropertyValue';

    public function __construct($propertyOne, $propertyTwo, $propertyThree)
    {
        $this->propertyOne = $propertyOne;
        $this->propertyTwo = $propertyTwo ?? $this->propertyTwo;
        $this->propertyThree = $propertyThree ?? $this->propertyThree;
    }
}
