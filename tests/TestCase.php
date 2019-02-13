<?php

declare(strict_types=1);

namespace LinioPay\Queue;

use Mockery;
use Mockery\Instantiator;
use PHPUnit\Framework\TestCase as TestCaseBase;
use ReflectionClass;
use Zend\Hydrator\Reflection;

class TestCase extends TestCaseBase
{
    /**
     * @var Reflection
     */
    protected static $hydrator;

    /**
     * @var Instantiator
     */
    protected static $instantiator;

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Injects a value into an inaccessible object property via reflection.
     *
     * @param mixed  $classOrObject
     * @param string $propertyNane
     * @param mixed  $propertyValue
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
     * @param $className
     * @param array $data
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
     * @return Reflection
     */
    protected function getHydrator()
    {
        if (!self::$hydrator) {
            self::$hydrator = new Reflection();
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
        if (!self::$instantiator) {
            self::$instantiator = new Instantiator();
        }

        return self::$instantiator;
    }

    /**
     * Allows obtaining a method from the given class.
     *
     * @param $className Name of the class being tested
     * @param $methodName Name of the method being tested
     *
     * @return callable
     */
    protected static function getMethod($className, $methodName)
    {
        $class = new ReflectionClass($className);
        $method = $class->getMethod($methodName);
        $method->setAccessible(true);

        return $method;
    }
}
