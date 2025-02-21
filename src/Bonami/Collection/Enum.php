<?php

declare(strict_types=1);

namespace Bonami\Collection;

use Bonami\Collection\Exception\InvalidEnumValueException;
use Bonami\Collection\Hash\IHashable;
use JsonSerializable;
use ReflectionClass;

use function array_combine;
use function is_object;

abstract class Enum implements IHashable, JsonSerializable
{
    /** @var array<string, Map<int|string, static>> */
    private static $instances = [];

    /** @var array<string, array<int|string, static>> */
    private static $instanceIndex;

    /** @var null|array<class-string<Enum>, array<int|string, string>> */
    private static $constNameIndex;

    /** @var int|string */
    private $value;

    /** @param int|string $value */
    final private function __construct($value)
    {
        $this->value = $value;
    }

    /**
     * @param mixed $value
     *
     * @return static
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
            self::$instanceIndex[$class] = $combined;
        }
        if (!isset(self::$instanceIndex[$class][$value])) {
            throw new InvalidEnumValueException($value, static::class);
        }

        return self::$instanceIndex[$class][$value];
    }

    /** @return EnumList<static> */
    public static function instanceList(): EnumList
    {
        return EnumList::fromIterable(self::instanceMap()->values());
    }

    /** @return Map<int|string, static> */
    public static function instanceMap(): Map
    {
        $class = static::class;

        if (isset(self::$instances[$class])) {
            return self::$instances[$class];
        }

        /** @var iterable<int, array{0: int|string, 1: static}> $pairs */
        $pairs = array_map(
            static function ($value) {
                return [$value, new static($value)];
            },
            self::getClassConstants()
        );

        return self::$instances[$class] = Map::fromIterable($pairs);
    }

    /** @return array<string> */
    private static function getClassConstants(): array
    {
        return (new ReflectionClass(static::class))->getConstants();
    }

    /**
     * @param static ...$enums
     *
     * @return EnumList<static>
     */
    public static function getListComplement(self ...$enums)
    {
        return self::instanceList()->minus($enums);
    }

    /**
     * @param int|string $value
     *
     * @return bool
     */
    public static function exists($value): bool
    {
        return static::instanceMap()->has($value);
    }

    public function getConstName(): string
    {
        self::lazyInitConstNameIndex();

        assert(self::$constNameIndex !== null);
        return self::$constNameIndex[static::class][$this->value];
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

    public function __toString()
    {
        return (string)$this->getValue();
    }

    /** @return int|string */
    public function getValue()
    {
        return $this->value;
    }

    public function hashCode(): int|string
    {
        return $this->getValue();
    }

    public function jsonSerialize(): string
    {
        return (string)$this->value;
    }
}
