<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\MessageBroker;

class HydrateFunctionFactory
{
    /**
     * @var callable[]
     */
    private $typeHydrateFunctionsCache = [];

    /**
     * Creates a hydration callable for a provided class name
     *
     * @param string $type
     * @return callable
     */
    public function createForType(string $type): callable
    {
        $type = $this->normalizeType($type);

        if (!isset($this->typeHydrateFunctionsCache[$type])) {
            $this->typeHydrateFunctionsCache[$type] = $type === \stdClass::class
                ? $this->createStdClassHydrateFunction()
                : $this->createHydrateFunctionFromClassReflection($type);
        }

        return $this->typeHydrateFunctionsCache[$type];
    }

    private function normalizeType(string $type): string
    {
        return strtolower($type) !== 'stdclass' ? $type : \stdClass::class;
    }

    private function createHydrateFunctionFromClassReflection($class): callable
    {
        $reflection = new \ReflectionClass($class);
        $prototype = $reflection->newInstanceWithoutConstructor();
        $properties = array_diff_key($reflection->getDefaultProperties(), $reflection->getStaticProperties());

        return (
            function (array $data) use ($prototype, $properties) {
                $instance = clone $prototype;
                foreach ($properties as $property => $defaultValue) {
                    $instance->{$property} = $data[$property] ?? $defaultValue;
                }
                return $instance;
            }
        )->bindTo(null, $class);
    }

    private function createStdClassHydrateFunction(): callable
    {
        return function (array $data) {
            return (object)$data;
        };
    }
}
