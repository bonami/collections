<?php

namespace Bonami\Collection;

use Bonami\Collection\Exception\InvalidEnumValueException;
use Bonami\Collection\Hash\IHashable;
use ReflectionClass;

use function array_combine;
use function is_object;

abstract class Enum implements IHashable
{

    /** @phpstan-var array<string, Map<int|string, static>> */
    private static $instances = [];
    /** @phpstan-var array<string, array<int|string, static>> */
    private static $instanceIndex;
    /** @phpstan-var array<string, array<int|string, string>> */
    private static $constNameIndex;
    /** @phpstan-var int|string */
    private $value;

    /**
     * @phpstan-param int|string $value
     */
    protected function __construct($value)
    {
         $this->value = $value;
    }

    /**
     * @phpstan-param mixed $value
     *
     * @phpstan-return static
     */
    public static function create($value)
    {
         $class = static::class;
        if (is_object($value)) {
              throw new InvalidEnumValueException($value, static::class);
        }
        if (!isset(self::$instanceIndex[$class])) {
              $instances = self::instanceList();
              $combined = array_combine($instances->getValues(), $instances->toArray());
              assert(is_array($combined));
              self::$instanceIndex[$class] = $combined;
        }
        if (!isset(self::$instanceIndex[$class][$value])) {
              throw new InvalidEnumValueException($value, static::class);
        }

        return self::$instanceIndex[$class][$value];
    }

    /**
     * @phpstan-return EnumList<static>
     */
    public static function instanceList(): EnumList
    {
         return EnumList::fromIterable(self::instanceMap()->values());
    }

    /**
     * @phpstan-param static ...$enums
     *
     * @phpstan-return EnumList<static>
     */
    public static function getListComplement(self ...$enums)
    {
         return self::instanceList()->minus($enums);
    }

    /**
     * @phpstan-return Map<int|string, static>
     */
    public static function instanceMap(): Map
    {
        $class = static::class;

        if (isset(self::$instances[$class])) {
            return self::$instances[$class];
        }

        /** @phpstan-var iterable<int, array{0: int|string, 1: static}> $pairs */
        $pairs = array_map(
            function ($value) {
                return [$value, new static($value)];
            },
            self::getClassConstants()
        );

        return self::$instances[$class] = Map::fromIterable($pairs);
    }

    /**
     * @phpstan-param int|string $value
     *
     * @phpstan-return bool
     */
    public static function exists($value): bool
    {
         return static::instanceMap()->has($value);
    }

    /**
     * @phpstan-return int|string
     */
    public function getValue()
    {
         return $this->value;
    }

    public function getConstName(): string
    {
         self::lazyInitConstNameIndex();

         return self::$constNameIndex[static::class][$this->value];
    }

    public function __toString()
    {
         return (string) $this->getValue();
    }

    public function hashCode()
    {
         return $this->getValue();
    }

    /**
     * @phpstan-return array<string>
     */
    private static function getClassConstants(): array
    {
         return (new ReflectionClass(static::class))->getConstants();
    }

    private static function lazyInitConstNameIndex(): void
    {
         $class = static::class;
        if (!isset(self::$constNameIndex)) {
              self::$constNameIndex = [];
        }

        if (!isset(self::$constNameIndex[$class])) {
              $constNameIndex = [];
            foreach (self::getClassConstants() as $constName => $value) {
                  $constNameIndex[$value] = $constName;
            }
            self::$constNameIndex[$class] = $constNameIndex;
        }
    }
}
