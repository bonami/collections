<?php

namespace Bonami\Collection;

use Bonami\Collection\Exception\InvalidEnumValueException;
use Bonami\Collection\Hash\IHashable;
use ReflectionClass;
use function array_combine;
use function is_object;

abstract class Enum implements IHashable {

	/** @var array */
	private static $instances;
	/** @var array */
	private static $instanceIndex;
	/** @var array */
	private static $constNameIndex;
	/** @var mixed */
	private $value;

	protected function __construct($value) {
		$this->value = $value;
	}

	/**
	 * @param mixed $value
	 *
	 * @return static
	 */
	public static function create($value) {
		$class = static::class;
		if (is_object($value)) {
			throw new InvalidEnumValueException($value, static::class);
		}
		if (!isset(self::$instanceIndex[$class])) {
			$instances = self::instanceList();
			self::$instanceIndex[$class] = array_combine($instances->getValues(), $instances->toArray());
		}
		if (!isset(self::$instanceIndex[$class][$value])) {
			throw new InvalidEnumValueException($value, static::class);
		}

		return self::$instanceIndex[$class][$value];
	}

	public static function instanceList() {
		return EnumList::fromIterable(self::instanceMap()->values());
	}

	public static function getListComplement(self ...$enums) {
		return self::instanceList()->minus($enums);
	}

	public static function instanceMap(): Map {
		$class = static::class;

		if (!isset(self::$instances)) {
			self::$instances = [];
		}

		if (!isset(self::$instances[$class])) {
			$items = [];
			foreach (self::getClassConstants($class) as $value) {
				$items[] = [$value, new static($value)];
			}
			self::$instances[$class] = new Map($items);
		}

		return self::$instances[$class];
	}

	public static function exists($value): bool {
		return static::instanceMap()->has($value);
	}

	public function getValue() {
		return $this->value;
	}

	public function getConstName(): string {
		$class = static::class;
		self::lazyInitConstNameIndex($class);

		return self::$constNameIndex[$class][$this->value];
	}

	public function __toString() {
		return (string) $this->getValue();
	}

	public function hashCode() {
		return $this->getValue();
	}

	private static function getClassConstants($class): array {
		$reflectionClass = new ReflectionClass($class);

		return $reflectionClass->getConstants();
	}

	private static function lazyInitConstNameIndex($class): void {
		if (!isset(self::$constNameIndex)) {
			self::$constNameIndex = [];
		}

		if (!isset(self::$constNameIndex[$class])) {
			$constNameIndex = [];
			foreach (self::getClassConstants($class) as $constName => $value) {
				$constNameIndex[$value] = $constName;
			}
			self::$constNameIndex[$class] = $constNameIndex;
		}
	}
}
