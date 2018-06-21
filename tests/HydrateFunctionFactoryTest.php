<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\MessageBroker;

use EcomDev\MessageBroker\Fixture\ClassWithDefaultPropertyValues;
use EcomDev\MessageBroker\Fixture\ClassWithPrivateAndProtectedProperties;
use EcomDev\MessageBroker\Fixture\ClassWithPublicProperties;
use EcomDev\MessageBroker\Fixture\ClassWithStaticProperties;
use PHPUnit\Framework\TestCase;

class HydrateFunctionFactoryTest extends TestCase
{
    /**
     * @var HydrateFunctionFactory
     */
    private $factory;

    protected function setUp()
    {
        $this->factory = new HydrateFunctionFactory();
    }

    /**
     * @test
     * @testWith ["stdClass"]
     *           ["StdClass"]
     *           ["stdclass"]
     *           ["STDClass"]
     *           ["sTdClass"]
     */
    public function hydratesStdClass($stdClassName)
    {
        $hydrator = $this->factory->createForType($stdClassName);

        $expectedObject = new \StdClass;
        $expectedObject->oneProperty = 'value_one';
        $expectedObject->secondProperty = 'value_two';

        $this->assertEquals($expectedObject, $hydrator([
            'oneProperty' => 'value_one',
            'secondProperty' => 'value_two'
        ]));
    }

    /** @test */
    public function hydratesClassWithPublicProperties()
    {
        $hydrator = $this->factory->createForType(ClassWithPublicProperties::class);

        $this->assertEquals(
            new ClassWithPublicProperties('value_one', 'value_two', 'value_three'),
            $hydrator([
                'propertyOne' => 'value_one',
                'propertyTwo' => 'value_two',
                'propertyThree' => 'value_three'
            ])
        );
    }

    /** @test */
    public function hydratesClassWithPrivateAndProtectedProperties()
    {
        $hydrator = $this->factory->createForType(ClassWithPrivateAndProtectedProperties::class);

        $this->assertEquals(
            new ClassWithPrivateAndProtectedProperties('value_one', 'value_two', 'value_three'),
            $hydrator([
                'propertyOne' => 'value_one',
                'propertyTwo' => 'value_two',
                'propertyThree' => 'value_three'
            ])
        );
    }

    /** @test */
    public function doesNotHydratePropertiesThatAreNotDeclaredInClass()
    {
        $hydrator = $this->factory->createForType(ClassWithPrivateAndProtectedProperties::class);

        $this->assertEquals(
            new ClassWithPrivateAndProtectedProperties('value_one', 'value_two', 'value_three'),
            $hydrator([
                'propertyOne' => 'value_one',
                'propertyTwo' => 'value_two',
                'propertyThree' => 'value_three',
                'propertyFour' => 'value_four'
            ])
        );
    }

    /** @test */
    public function setsDefaultPropertyValueWhenDataIsMissing()
    {
        $hydrator = $this->factory->createForType(ClassWithDefaultPropertyValues::class);

        $this->assertEquals(
            new ClassWithDefaultPropertyValues('value_one'),
            $hydrator([
                'propertyOne' => 'value_one'
            ])
        );
    }

    /** @test */
    public function keepsStaticPropertiesInTact()
    {
        $hydrator = $this->factory->createForType(ClassWithStaticProperties::class);

        $this->assertEquals(
            new ClassWithStaticProperties('value_one', 'value_two', 'value_three'),
            $hydrator([
                'propertyOne' => 'value_one',
                'propertyTwo' => 'value_two',
                'propertyThree' => 'value_three',
                'staticProperty' => 'value_four',
            ])
        );
    }

    /**
     * @test
     * @testWith ["stdClass"]
     *           ["EcomDev\\MessageBroker\\Fixture\\ClassWithStaticProperties"]
     *           ["EcomDev\\MessageBroker\\Fixture\\ClassWithDefaultPropertyValues"]
     *           ["EcomDev\\MessageBroker\\Fixture\\ClassWithPrivateAndProtectedProperties"]
     */
    public function returnsTheSameFactoryForTheSameTypeOnEveryCall($type)
    {
        $this->assertSame($this->factory->createForType($type), $this->factory->createForType($type));
    }
}
