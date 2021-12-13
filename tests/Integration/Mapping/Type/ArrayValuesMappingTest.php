<?php

declare(strict_types=1);

namespace CuyZ\Valinor\Tests\Integration\Mapping\Type;

use CuyZ\Valinor\Mapper\MappingError;
use CuyZ\Valinor\Mapper\Tree\Exception\CannotCastToScalarValue;
use CuyZ\Valinor\Mapper\Tree\Exception\InvalidNodeValue;
use CuyZ\Valinor\Tests\Integration\IntegrationTest;
use CuyZ\Valinor\Tests\Integration\Mapping\Fixture\AnotherSimpleObject;
use CuyZ\Valinor\Tests\Integration\Mapping\Fixture\SimpleObject;
use CuyZ\Valinor\Tests\Integration\Mapping\Fixture\SimpleObject as SimpleObjectAlias;

final class ArrayValuesMappingTest extends IntegrationTest
{
    public function test_values_are_mapped_properly(): void
    {
        $source = [
            'booleans' => [true, false, true],
            'floats' => [42.404, 404.42],
            'integers' => [42, 404, 1337],
            'strings' => ['foo', 'bar', 'baz'],
            'arrayWithDefaultKeyType' => [42 => 'foo', 'some-key' => 'bar'],
            'arrayWithIntegerKeyType' => [1337 => 'foo', 42.0 => 'bar', '404' => 'baz'],
            'arrayWithStringKeyType' => [1337 => 'foo', 42.0 => 'bar', 'some-key' => 'baz'],
            'simpleArray' => [42 => 'foo', 'some-key' => 'bar'],
            'objects' => [
                'foo' => ['value' => 'foo'],
                'bar' => ['value' => 'bar'],
                'baz' => ['value' => 'baz'],
            ],
            'objectsWithAlias' => [
                'foo' => ['value' => 'foo'],
                'bar' => ['value' => 'bar'],
                'baz' => ['value' => 'baz'],
            ],
            'nonEmptyArraysOfStrings' => ['foo', 'bar', 'baz'],
            'nonEmptyArrayWithDefaultKeyType' => [42 => 'foo', 'some-key' => 'bar'],
            'nonEmptyArrayWithIntegerKeyType' => [1337 => 'foo', 42.0 => 'bar', '404' => 'baz'],
            'nonEmptyArrayWithStringKeyType' => [1337 => 'foo', 42.0 => 'bar', 'some-key' => 'baz'],
            'arrayOfUnionOfObjects' => [
                ['value' => 'foo'],
                ['anotherValue' => 'bar'],
            ],
        ];

        foreach ([ArrayValues::class, ArrayValuesWithConstructor::class] as $class) {
            try {
                $result = $this->mapperBuilder->mapper()->map($class, $source);
            } catch (MappingError $error) {
                $this->mappingFail($error);
            }

            self::assertSame($source['booleans'], $result->booleans);
            self::assertSame($source['floats'], $result->floats);
            self::assertSame($source['integers'], $result->integers);
            self::assertSame($source['strings'], $result->strings);
            self::assertSame($source['arrayWithDefaultKeyType'], $result->arrayWithDefaultKeyType);
            self::assertSame($source['arrayWithIntegerKeyType'], $result->arrayWithIntegerKeyType);
            self::assertSame($source['arrayWithStringKeyType'], $result->arrayWithStringKeyType);
            self::assertSame($source['simpleArray'], $result->simpleArray);
            self::assertSame('foo', $result->objects['foo']->value);
            self::assertSame('bar', $result->objects['bar']->value);
            self::assertSame('baz', $result->objects['baz']->value);
            self::assertSame('foo', $result->objectsWithAlias['foo']->value);
            self::assertSame('bar', $result->objectsWithAlias['bar']->value);
            self::assertSame('baz', $result->objectsWithAlias['baz']->value);
            self::assertSame($source['nonEmptyArraysOfStrings'], $result->nonEmptyArraysOfStrings);
            self::assertSame($source['nonEmptyArrayWithDefaultKeyType'], $result->nonEmptyArrayWithDefaultKeyType);
            self::assertSame($source['nonEmptyArrayWithIntegerKeyType'], $result->nonEmptyArrayWithIntegerKeyType);
            self::assertSame($source['nonEmptyArrayWithStringKeyType'], $result->nonEmptyArrayWithStringKeyType);
            self::assertSame('foo', $result->arrayOfUnionOfObjects[0]->value);
            self::assertSame('bar', $result->arrayOfUnionOfObjects[1]->anotherValue);
        }
    }

    public function test_empty_array_in_non_empty_array_throws_exception(): void
    {
        try {
            $this->mapperBuilder->mapper()->map(ArrayValues::class, [
                'nonEmptyArraysOfStrings' => [],
            ]);
        } catch (MappingError $exception) {
            $error = $exception->describe()['nonEmptyArraysOfStrings'][0];

            self::assertInstanceOf(InvalidNodeValue::class, $error);
            self::assertSame(1630678334, $error->getCode());
            self::assertSame('Empty array is not accepted by `non-empty-array<string>`.', $error->getMessage());
        }
    }

    public function test_value_that_cannot_be_casted_throws_exception(): void
    {
        try {
            $this->mapperBuilder->mapper()->map(ArrayValues::class, [
                'integers' => ['foo'],
            ]);
        } catch (MappingError $exception) {
            $error = $exception->describe()['integers.0'][0];

            self::assertInstanceOf(CannotCastToScalarValue::class, $error);
            self::assertSame(1618736242, $error->getCode());
            self::assertSame('Cannot cast value of type `string` to `int`.', $error->getMessage());
        }
    }
}

class ArrayValues
{
    /** @var array<bool> */
    public array $booleans;

    /** @var array<float> */
    public array $floats;

    /** @var array<int> */
    public array $integers;

    /** @var array<string> */
    public array $strings;

    /** @var array<array-key, string> */
    public array $arrayWithDefaultKeyType;

    /** @var array<int, string> */
    public array $arrayWithIntegerKeyType;

    /** @var array<string, string> */
    public array $arrayWithStringKeyType;

    /** @var string[] */
    public array $simpleArray;

    /** @var array<SimpleObject> */
    public array $objects;

    /** @var array<SimpleObjectAlias> */
    public array $objectsWithAlias;

    /** @var non-empty-array<string> */
    public array $nonEmptyArraysOfStrings = ['foo'];

    /** @var non-empty-array<array-key, string> */
    public array $nonEmptyArrayWithDefaultKeyType = ['foo'];

    /** @var non-empty-array<int, string> */
    public array $nonEmptyArrayWithIntegerKeyType = ['foo'];

    /** @var non-empty-array<string, string> */
    public array $nonEmptyArrayWithStringKeyType = ['foo' => 'bar'];

    /** @var array<SimpleObject|AnotherSimpleObject> */
    public array $arrayOfUnionOfObjects;

}

class ArrayValuesWithConstructor extends ArrayValues
{
    /**
     * @param array<bool> $booleans
     * @param array<float> $floats
     * @param array<int> $integers
     * @param array<string> $strings
     * @param array<array-key, string> $arrayWithDefaultKeyType
     * @param array<int, string> $arrayWithIntegerKeyType
     * @param array<string, string> $arrayWithStringKeyType
     * @param string[] $simpleArray
     * @param array<SimpleObject> $objects
     * @param array<SimpleObjectAlias> $objectsWithAlias
     * @param non-empty-array<string> $nonEmptyArraysOfStrings
     * @param non-empty-array<array-key, string> $nonEmptyArrayWithDefaultKeyType
     * @param non-empty-array<int, string> $nonEmptyArrayWithIntegerKeyType
     * @param non-empty-array<string, string> $nonEmptyArrayWithStringKeyType
     * @param array<SimpleObject|AnotherSimpleObject> $arrayOfUnionOfObjects
     */
    public function __construct(
        array $booleans,
        array $floats,
        array $integers,
        array $strings,
        array $arrayWithDefaultKeyType,
        array $arrayWithIntegerKeyType,
        array $arrayWithStringKeyType,
        array $simpleArray,
        array $objects,
        array $objectsWithAlias,
        array $nonEmptyArraysOfStrings,
        array $nonEmptyArrayWithDefaultKeyType,
        array $nonEmptyArrayWithIntegerKeyType,
        array $nonEmptyArrayWithStringKeyType,
        array $arrayOfUnionOfObjects
    ) {
        $this->booleans = $booleans;
        $this->floats = $floats;
        $this->integers = $integers;
        $this->strings = $strings;
        $this->arrayWithDefaultKeyType = $arrayWithDefaultKeyType;
        $this->arrayWithIntegerKeyType = $arrayWithIntegerKeyType;
        $this->arrayWithStringKeyType = $arrayWithStringKeyType;
        $this->simpleArray = $simpleArray;
        $this->objects = $objects;
        $this->objectsWithAlias = $objectsWithAlias;
        $this->nonEmptyArraysOfStrings = $nonEmptyArraysOfStrings;
        $this->nonEmptyArrayWithDefaultKeyType = $nonEmptyArrayWithDefaultKeyType;
        $this->nonEmptyArrayWithIntegerKeyType = $nonEmptyArrayWithIntegerKeyType;
        $this->nonEmptyArrayWithStringKeyType = $nonEmptyArrayWithStringKeyType;
        $this->arrayOfUnionOfObjects = $arrayOfUnionOfObjects;
    }
}
