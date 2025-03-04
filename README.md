# Collections for PHP with focus on Immutability and Functional Programming
![Build Status](https://github.com/bonami/collections/workflows/CI/badge.svg)
[![Latest Stable Version](https://poser.pugx.org/bonami/collections/v/stable)](https://packagist.org/packages/bonami/collections)
[![License](https://poser.pugx.org/bonami/collections/license)](https://packagist.org/packages/bonami/collections)

## Table of contents
- [Motivation](#motivation)
- [Show me the code!](#motivation)
- [Features](#features)
    - [Structures](#structures)
    - [Type safety](#type-safety)
- Advanced topics:
  - [Type classes](./docs/type-classes.md)
  - [Lift operator](./docs/lift.md)
  - [Currying](./docs/curried-function.md)
  - [Traverse](#traverse)
- [License](#features)
- [Contributing](#features)

## Motivation

Why yet another collections library for PHP? Native PHP arrays or SPL structures like SplFixedArray or SplObjectStorage(and other) are mutable and has very strange interfaces and behaviors. They often represent more data structures at once (eg. SplObjectStorage represents both Set and Map) and theirs interfaces are designed for classic imperative approach.

We tried to design interfaces of our structures to be focused on declarative approach leveraging functional programing. For more safety, we designed structures to be immutable (we have some mutables as well, because sometime it is necessary for performance reasons)

All the code is designed to be type safe with [phpstan generics](#type-safety).

## Show me the code!

A code example is worth a thousand words, so here are some simple examples:

### Filtering Person DTOs and extracting some information 

```php
use Bonami\Collection\ArrayList;

class Person {

	public function __construct(
		private readonly string $name,
		private readonly int $age
	) {}

}

$persons = ArrayList::of(new Person('John', 31), new Person('Jacob', 22), new Person('Arthur', 29));
$names = $persons
	->filter(fn (Person $person): bool => $person->age <= 30)
	->sort(fn (Person $a, Person $b): int => $a->name <=> $b->name)
	->map(fn (Person $person): string => $person->name)
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

### Generating combinations with [lift](#lift-operator)

```php
use Bonami\Collection\ArrayList;

$concat = fn (string $first, string $second) => "{$first} {$second}";
$coloredObjects = ArrayList::lift2($concat)($colors, $objects);
```

### Character frequency analysis

```php
use Bonami\Collection\ArrayList;
use Bonami\Collection\Map;
use function Bonami\Collection\id;
use function Bonami\Collection\descendingComparator;

function frequencyAnalysis(string $text): Map {
	$chars = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY);
	return ArrayList::fromIterable($chars)
		->groupBy(id(...))
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
- [`\Bonami\Collection\Option`](./docs/option.md) - Immutable structure for representing, that you maybe have value and maybe not. It provides safe (functional) approach to handle null pointer errors.
- [`\Bonami\Collection\TrySafe`](./docs/try-safe.md) - Immutable structure for representing,  that you have value or error generated upon the way. It provides safe (functional) approach to handle errors without side effects.
- [`\Bonami\Collection\CurriedFunction`](./docs/curried-function.md) - Represents single argument function. It can create curried version of multi argument function, which is better for some function programming composition patterns.

### Type safety

We are using [phpstan](https://phpstan.org/) annotations for better type safety, utilizing generics. For even better type
resolving, we created optional dependency [phpstan-collections](https://github.com/bonami/phpstan-collections), which we strongly
suggest installing if you use phpstan. It fixes some type resolving, especially for late static binding.

### Traverse
 
You may find yourself in situation, where you map list using mapper function which returns values wrapped in `Option` but you'd rather have values unwrapped. And that is when `traverse` method comes handy:

```php
use Bonami\Collection\ArrayList;
use Bonami\Collection\Option;

$getUserNameById = function(int $id): Option {
	$userNamesById = [
		1 => "John",
		2 => "Paul",
		3 => "George",
		4 => "Ringo",
	];
	return Option::fromNullable($userNamesById[$id] ?? null);
};

print Option::traverse(ArrayList::fromIterable([1, 3, 4]), $getUserNameById); 
// Some([John, Paul, Ringo])
``` 

Compare the result with usage of our old buddy `ArrayList::map`: 

```php
use Bonami\Collection\ArrayList;
use Bonami\Collection\Option;

$getUserNameById = function(int $id): Option {
	$userNamesById = [
		1 => "John",
		2 => "Paul",
		3 => "George",
		4 => "Ringo",
	];
	return Option::fromNullable($userNamesById[$id] ?? null);
};

print ArrayList::fromIterable([1, 3, 4])
    ->map($getUserNameById);
// [Some(John), Some(George), Some(Ringo)]
``` 

Did you spot the difference? We have list of options with strings inside here whereas we have option of list with strings inside in the first code example.

So `traverse` allows us to convert list of `Options` to `Option` of list with unwrapped values. And guess what - as usual, `None` will ruin everything:   

```php
use Bonami\Collection\ArrayList;
use Bonami\Collection\Option;

$getUserNameById = function(int $id): Option {
	$userNamesById = [
		1 => "John",
		2 => "Paul",
		3 => "George",
		4 => "Ringo",
	];
	return Option::fromNullable($userNamesById[$id] ?? null);
};

print Option::traverse(ArrayList::fromIterable([1, 3, 666]), $getUserNameById); 
// None
``` 

Usage of `traverse` method is not limited to `Option` class. It will work with any applicative, so it is available for `TrySafe`, `ArrayList` & `LazyList` (`Failure` & empty list instances behave the same way as `None`).

## License

This package is released under the [MIT license](LICENSE).

## Contributing

If you wish to contribute to the project, please read the [CONTRIBUTING notes](CONTRIBUTING.md).
