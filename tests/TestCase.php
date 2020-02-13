<?php

declare(strict_types=1);

namespace LinioPay\Idle;

use Laminas\Hydrator\ReflectionHydrator;
use Mockery;
use Mockery\Instantiator;
use PHPUnit\Framework\TestCase as TestCaseBase;
use ReflectionClass;
use ReflectionMethod;

class TestCase extends TestCaseBase
{
    /**
     * @var ReflectionHydrator|null
     */
    protected static $hydrator;

    /**
     * @var Instantiator|null
     */
    protected static $instantiator;

    /**
     * {@inheritdoc}
     */
    protected function tearDown() : void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Injects a value into an inaccessible object property via reflection.
     *
     * @param mixed $classOrObject
     * @param mixed $propertyValue
     */
    protected function inject($classOrObject, string $propertyNane, $propertyValue)
    {
        $property = new \ReflectionProperty($classOrObject, $propertyNane);
        $property->setAccessible(true);
        $property->setValue($classOrObject, $propertyValue);
    }

    /**
     * Creates a fake $className and injects any provided properties. This is a
     * quick way to create an instance of a class that requires constructor
     * arguments when they may not be relevant to what you are testing.
     *
     * "Fake objects actually have working implementations, but usually take
     *  some shortcut which makes them not suitable for production."
     *
     * @see http://martinfowler.com/articles/mocksArentStubs.html
     *
     * @param string $className
     *
     * @return object
     */
    protected function fake($className, array $data = [])
    {
        return $this->getHydrator()->hydrate($data, $this->getInstantiator()->instantiate($className));
    }

    /**
     * Uses the Zend Reflection hydrator to populate an object's properties.
     *
     * @return ReflectionHydrator
     */
    protected function getHydrator()
    {
        if (is_null(self::$hydrator)) {
            self::$hydrator = new ReflectionHydrator();
        }

        return self::$hydrator;
    }

    /**
     * Uses the Mockery Instantiator to create an object without calling its constructor.
     *
     * @return Instantiator
     */
    protected function getInstantiator()
    {
        if (is_null(self::$instantiator)) {
            self::$instantiator = new Instantiator();
        }

        return self::$instantiator;
    }

    /**
     * Allows obtaining a method from the given class.
     *
     * @param string $className  Name of the class being tested
     * @param string $methodName Name of the method being tested
     *
     * @return ReflectionMethod
     */
    protected static function getMethod($className, $methodName)
    {
        $class = new ReflectionClass($className);
        $method = $class->getMethod($methodName);
        $method->setAccessible(true);

        return $method;
    }
}
