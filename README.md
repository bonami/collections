# Collections for PHP with focus on Immutability and Functional Programming
![Build Status](https://github.com/bonami/collections/workflows/CI/badge.svg)
[![Latest Stable Version](https://poser.pugx.org/bonami/collections/v/stable)](https://packagist.org/packages/bonami/collections)
[![License](https://poser.pugx.org/bonami/collections/license)](https://packagist.org/packages/bonami/collections)

## Table of contents
- [Motivation](#motivation)
- [Show me the code!](#motivation)
- [Features](#features)
    - [Structures](#structures)
    - [Type classes](#type-classes)
    - [Currying](#currying)
- [License](#features)
- [Contributing](#features)

## Motivation

Why yet another collections library for PHP? Native PHP arrays or SPL structures like SplFixedArray or SplObjectStorage(and other) are mutable and has very strange interfaces and behaviors. They often represent more data structures at once (eg. SplObjectStorage represents both Set and Map) and theirs interfaces are designed for classic imperative approach.

We tried to design interfaces of our structures to be focused on declarative approach leveraging functional programing. For more safety, we designed structures to be immutable (we have some mutables as well, because sometime it is necessary for performance reasons)

## Show me the code!

A code example is worth a thousand words, so here are some simple examples:

### Filtering Person DTOs and extracting some information 

```php
use Bonami\Collection\ArrayList;

class Person {

	private string $name;
	private int $age;

	public function __construct(string $name, int $age) {
		$this->name = $name;
		$this->age = $age;
	}

	public function getName(): string {
		return $this->name;
	}

	public function getAge(): int {
		return $this->age;
	}

}

$persons = ArrayList::of(new Person('John', 31), new Person('Jacob', 22), new Person('Arthur', 29));
$names = $persons
	->filter(fn (Person $person): bool => $person->getAge() <= 30)
	->sort(fn (Person $a, Person $b): int => $a->getName() <=> $b->getName())
	->map(fn (Person $person): string => $person->getName())
	->join(";");

// $names = "Arthur;Jacob"
```

### Generating combinations

```php
use Bonami\Collection\ArrayList;

$colors = ArrayList::fromIterable(['red', 'green', 'blue']);
$objects = ArrayList::fromIterable(['car', 'pencil']);

$coloredObjects = ArrayList::fromIterable($colors)
	->flatMap(fn (string $color) => $objects->map(fn (string $object) => "{$color} {$object}"))

// $coloredObjects = ArrayList::of('red car', 'red pencil', 'green car', 'green pencil', 'blue car', 'blue pencil')
```

### Generating combinations with lift

```php
use Bonami\Collection\ArrayList;

$concat = fn (string $first, string $second) => "{$first} {$second}";
$coloredObjects = ArrayList::lift($concat)($colors, $objects);
```

### Character frequency analysis

```php
use Bonami\Collection\ArrayList;
use Bonami\Collection\Map;
use function Bonami\Collection\identity;
use function Bonami\Collection\descendingComparator;

function frequencyAnalysis(string $text): Map {
	$chars = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY);
	return ArrayList::fromIterable($chars)
		->groupBy(identity())
		->mapValues(fn (ArrayList $group): int => $group->count());
}

$text = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Cras nec mi rhoncus, dignissim tortor ac,' .
    ' aliquam metus. Maecenas non hendrerit tellus. Nam molestie augue ac lectus cursus consequat. Nunc ' .
    'ultrices metus sit amet nulla blandit lacinia. Nam vestibulum ultrices mollis. Morbi consequat ante non ' .
    'ornare lobortis. Nullam enim mauris, tempus quis auctor eu, condimentum dignissim nunc. Integer dapibus ' .
    'dolor eu nisl euismod sagittis. Phasellus magna ante, pharetra eget nisi vehicula, elementum lacinia dui. ' .
    'Aliquam semper at eros a sodales. In a rhoncus sapien. Integer blandit volutpat nisl. Donec vitae massa eget ' .
    'mauris dignissim cursus nec et erat. Suspendisse consectetur ac quam sit amet pretium.';

// top ten characters by number of occurrences
$top10 = frequencyAnalysis($text)
    ->sortValues(descendingComparator())
    ->take(10);
```

## Features

### Structures

- `\Bonami\Collection\ArrayList` - An immutable (non associative) array wrapper, meant for sequential processing.
- `\Bonami\Collection\Map` - An immutable key-value structure. It can contain any kind of object as keys (with some limitation, see further info in docs).
- `\Bonami\Collection\Mutable\Map` - Mutable variant of Map.
- `\Bonami\Collection\LazyList` - Wrapper on any iterable structure. It leverages yield internally making it lazy. It can save memory significantly.
- [`\Bonami\Collection\Enum`](./docs/enum.md) - Not a collection, but has great synergy with rest of the library. Meant for defining closed enumerations. Provides interesting methods like getting complements list of values for given enum.  
- `\Bonami\Collection\EnumList` - List of Enums, extending ArrayList
- `\Bonami\Collection\Option` - Immutable structure for representing, that you maybe have value and maybe not. It provides safe (functional) approach to handle null pointer errors.
- `\Bonami\Collection\TrySafe` - Immutable structure for representing,  that you have value or error generated upon the way. It provides safe (functional) approach to handle errors without side effects.
- `\Bonami\Collection\Lambda` - Wrapper around callable providing currying. Currying is very useful for some functional patterns

### Type classes

- `\Bonami\Collection\ArrayList`, `\Bonami\Collection\LazyList`, `\Bonami\Collection\Option`, `\Bonami\Collection\TrySafe` are Monads, which means that they support
    - `->map` with functorial laws
    - `::of` (pure) and `->ap` (apply) with applicative laws
    - `->flatMap` (bind) with monadic laws
    - on top of that they support many friendly functional methods (like `exists`, `all`, `find` etc.) 

### Currying

If higher order functions are said to be bread & butter of functional programming then currying is ... well ... the spice of it. The concept is simple - it is transforming function taking multiple arguments into sequence of functions each taking single argument.

Since PHP does not provide us with any means of currying, we implemented own callable wrapper `Lambda` capable of the task:

```php
$greeter = fn (string $greeting, string $name): string => "{$greeting} {$name}!";

$helloGreeter = Lambda::of($greeter)("Hello");
echo $helloGreeter("John"); // Hello John!
echo $helloGreeter("Paul"); // Hello Paul!

$holaGreeter = Lambda::of($greeter)("Hola");
echo $holaGreeter("Diego"); // Hola Diego!
```

To be more developer friendly, it is possible to call functions either with multiple arguments or with single argument per call or even combine the both:

```php
$sumThree = Lambda::of(fn (int $x, int $y, int $z): int => $x + $y + $z);

$sumThree(7)(42)(666);
$sumThree(7)(42, 666);
$sumThree(7, 42)(666);
$sumThree(7, 42, 666);
```

All invocations will yield the same result.

## License

This package is released under the [MIT license](LICENSE).

## Contributing

If you wish to contribute to the project, please read the [CONTRIBUTING notes](CONTRIBUTING.md).
