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
    - [Option](#option)
    - [TrySafe](#trysafe)
    - [Lift operator](#lift-operator)
    - [Traverse](#traverse)
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

### Generating combinations with [lift](#lift-operator)

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
- [`\Bonami\Collection\Option`](#option) - Immutable structure for representing, that you maybe have value and maybe not. It provides safe (functional) approach to handle null pointer errors.
- [`\Bonami\Collection\TrySafe`](#trysafe) - Immutable structure for representing,  that you have value or error generated upon the way. It provides safe (functional) approach to handle errors without side effects.
- `\Bonami\Collection\Lambda` - Wrapper around callable providing currying. Currying is very useful for some functional patterns

### Type classes

- `\Bonami\Collection\ArrayList`, `\Bonami\Collection\LazyList`, `\Bonami\Collection\Option`, `\Bonami\Collection\TrySafe` are Monads, which means that they support
    - `->map` with functor laws
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

### Option

> aka how to avoid billion dollar mistake by using `Option` (we are looking in your direction, `null`!)

`Option` type encapsulates value, which may or may not exist. If you are not familiar with concept of `Option` (also called `Maybe` in some languages), think of `ArrayList` which is either empty or has single item inside.

Value which exists is represented by instance of `Some` class, whereas missing one is `None`.

```php
use Bonami\Collection\Option;

$somethingToEat = Option::some("ice cream");
$nothingToSeeHere = Option::none();
```

The good thing is that we can operate on `Some` & `None` the same way: 

```php 
use Bonami\Collection\Option;

$somethingToEat = Option::some("ice cream");
$nothingToSeeHere = Option::none();

$iLikeToEat = fn (string $food): string => "I like to eat tasty {$food}!"; 

$somethingToEat->map($iLikeToEat); // Will map to string "I like to eat tasty ice cream!" wrapped in `Some` instance
$nothingToSeeHere->map($iLikeToEat); // `None`, wont be mapped and will stay the same

``` 
 
We can use `Option` as better and more safe alternative to nullable values since handling of `null` may easily become cumbersome. Compare following code examples:

```php
function getUserEmailById(int $id): ?string {
    $usersDb = [
        1 => "john@foobar.baz",
        2 => "paul@foobar.baz",
    ];
    return $usersDb[$id] ?? null;
} 
function getAgeByUserEmail(string $email): ?int {
    $ageDb = [
        "john@foobar.baz" => 66,
        "diego@hola.esp" => 42,
    ];
    return $ageDb[$email] ?? null;
}

// The old good `null` way
function printUserAgeById(int $id): void {
    $email = getUserEmailById($id);
    $age = null;
    if ($email !== null) {
        $age = getAgeByUserEmail($email);
   
    }
    if ($age === null) {
        print "Dont know age of user with id {$id}";
    } else {
        print "Age of user with id {$id} is {$age}";
    }   
     
}

```

```php
use Bonami\Collection\Option;

function getUserEmailById(int $id): ?string {
    $usersDb = [
        1 => "john@foobar.baz",
        2 => "paul@foobar.baz",
    ];
    return $usersDb[$id] ?? null;
} 
function getAgeByUserEmail(string $email): ?int {
    $ageDb = [
        "john@foobar.baz" => 66,
        "diego@hola.esp" => 42,
    ];
    return $ageDb[$email] ?? null;
}

// The better way using `Option` 
function printUserAgeById(int $id): void {
    print Option::fromNullable(getUserEmailById($id))
        ->flatMap(fn (string $email) => Option::fromNullable(getAgeByUserEmail($email)))
        ->map(fn (int $age): string => "Age of user with id {$id} is {$age}")
        ->getOrElse("Dont know age of user with id {$id}");

}
```

You can see that the second example using `Option` allows us to sequence computations so that if any of intermediate steps yields `None`, the subsequent computations are simply ignored.
We hope you have a grasp of it, even though example is rather artificial ;-)

In case you are a functional programming zealot, you'd like to hear that `Option` is a lawful monad (thus functor & applicative).

### TrySafe

Make long story short: what `Option` is for possible missing (nullable) values, `TrySafe` is for possible exceptions.

Throwing exceptions and catching them somewhere may seem as a good way how to handle error states throughout your code. Except that it is not...

Consider following code - can you guess the outcome of calling possible implementation of `compute` method from signature?
```php
interface Div {
    public function compute(int $a, int $b): float;
}
```
Most of the time, the method will be well-behaved and will return `float` according to return type declaration. Until we pass `0` as second argument:

```php
class DivImplementation {
    public function compute(int $a, int $b): float {
        if ($b === 0) {
            throw new RuntimeException("Can not divide by zero, bro");
        }
        return $a / $b;
    }
}
```
The problem is that `compute` is not defined for every possible combination of arguments (it is [partial function](https://en.wikipedia.org/wiki/Partial_function)) and the signature is kind of lying to us. We must look at the specific function implementation body to see if there is possibility for exception to be thrown. 

We can do better with the little help of `TrySafe` class:

```php
use Bonami\Collection\TrySafe;

interface Div {
    public function compute(int $a, int $b): TrySafe;
}

class DivImplementation {
    public function compute(int $a, int $b): TrySafe {
        return $b === 0
            ? TrySafe::failure(new RuntimeException("Can not divide by zero, bro"))
            : TrySafe::success($a / $b);
    }
}

$div = new DivImplementation();
$outcome = $div->compute(10, 0);

```

Now we have the outcome of `compute` call safely wrapped in `TrySafe` instances and can do the further computations with it no matter if it is `Success` or `Failure`.
 
You can use `TrySafe` to wrap unsafe (throwing) calls and chain the computations the same way as with `Option`:

```php
use Bonami\Collection\TrySafe;

$getTheUltimateAnswerOrThrow = function(bool $shouldThrow): int {
    if ($shouldThrow) {
        throw new RuntimeException("There is no ultimate answer!");
    }
    return 42;
};

$makeTheAnswerBiggerOrThrow = function(int $answer): int {
  if ($answer !== 42) {
      throw new RuntimeException("Unlucky!");
  }
  return $answer + 624;
};

TrySafe::fromCallable(fn() => $getTheUltimateAnswerOrThrow(true))
    ->flatMap(fn (int $answer) => TrySafe::fromCallable(fn() => $makeTheAnswerBiggerOrThrow($answer)))
    ->resolve (
        function(Throwable $e): void { 
            print $e->getMessage();
        },
        function(int $biggerAnswer): void { 
            print "The ultimate answer is {$biggerAnswer}";
        }
    );
```
 
_Disclaimer:_ As you probably noticed, we reflect possible exception in return type now, but on the other side, we've lost the information that wrapped success value is `float`.  This applies also to `Option`, `ArrayList` etc. Unfortunately there is no silver bullet solution, until PHP have the generics implemented (but hey, have look at [phpstan generics templates](https://medium.com/@ondrejmirtes/generics-in-php-using-phpdocs-14e7301953)). 

### Lift operator

Lifting function into (applicative) context means transforming it so it can operate on values wrapped in that context.

Example of lifting function into `Option` context:

```php
use Bonami\Collection\Option;

// Get our configuration value wrapped in Option, because it can be missing 
function getConfig(string $key): Option {
    $config = [
        'host' => "domain.tld",
        'port' => 8080,
    ];
    return Option::fromNullable($config[$key] ?? null);
}

// Our super usefull method accepts string host, int port & returns uri string
$createUri = fn (string $host, int $port): string => "https://{$host}:{$port}";

// Unfortunately our string & int are wrapped in Option context, what now?
$hostOption = getConfig('host');
$portOption = getConfig('port');

// Lifting method will allow to pass values wrapped in Option!
$createUriLifted = Option::lift($createUri);

// Return value is wrapped in Option too
print $createUriLifted($hostOption, $portOption); // Some(https://domain.tld:8080)
```

Please note, that `None` behaves very greedily here - if any of arguments passed into lifted function is `None` the result will be `None` too. Lifting function into `TrySafe` context works analogously. 

As we said earlier `Option` & `ArrayList` are very look-alike. Both can represent that value is missing or present and on top of that `ArrayList` can represent that there is more than one value. We can do some pretty sick tricks using function lifted in `ArrayList` (`LazyList`) context:

```php
use Bonami\Collection\ArrayList;

$hosts = ArrayList::fromIterable(['foo.org', 'bar.io']);
$ports = ArrayList::fromIterable([80, 8080]);

$createUri = fn (string $host, int $port): string => "https://{$host}:{$port}";

// Accepts list of strings, list of ints & returns list of uris created from all possible combinations  
$createUriLifted = ArrayList::lift($createUri);

print $createUriLifted($hosts, $ports); // [https://foo.org:80, https://bar.io:80, https://foo.org:8080, https://bar.io:8080]

```

Lifted function generates all possible combinations of hosts & ports for us, which can be very handy!

Please note that empty `ArrayList` would behave the same way as `None` in `Option` context:
```php
use Bonami\Collection\ArrayList;

$hosts = ArrayList::fromIterable(['foo.org', 'bar.io']);
$ports = ArrayList::fromEmpty();

$createUri = fn (string $host, int $port): string => "https://{$host}:{$port}";

$createUriLifted = ArrayList::lift($createUri);

print $createUriLifted($hosts, $ports); // [] 

```
This is not coincidence - `Option::none()`, `ArrayList::fromEmpty()`, `LazyList::fromEmpty()`, `TrySafe::failure($ex)` they all create an instance expressing nonexistence of value and are analogous to zero in context of multiplication (empty set in cartesian product for these type classes actually).

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
