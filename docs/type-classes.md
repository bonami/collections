# Type classes

Type class is bigger set of classes, that has some common behavior we wont to abstract. 
Php has very basic type system and even with phpstan it is hard to do encode it somehow meaningfully.

Ideally we would have higher kinder generics, which we haven't. So we chose traits, because we can
exploit the way how they are mixed in. We can use `self` and `static` keyword in those traits and they
are bounded to the class they are mixed into.

Also we can mixin multiple type-class trairs into class, which is something we actually want to do.

Type-class traits we define:
- `\Bonami\Collection\Applicative1` applicative with 1 hole for generic type
- `\Bonami\Collection\Applicative2` applicative with 2 holes for generic types
- `\Bonami\Collection\Monad1` monad with 1 hole for generic type, uses `\Bonami\Collection\Applicative1`
- `\Bonami\Collection\Monad2` monad with 2 holes for generic types, uses `\Bonami\Collection\Monad2`
- `\Bonami\Collection\Iterable1` iterable type with 1 hole for generic type

## Applicative

Applicative defines abstract methods:
- `pure` - wraps single value into `Applicative` instance. Or more theoretically: pulls impure value into `Applicative` instance context.
- `product` - creates product of two values in context of `Applicative`. Simply put, combines to values into array tupple wrapped in `Applicative` instance
- `map` - maps over values in context of `Applicative`

Class that mixin this trait needs to implement them. It should implement them in a way, that they obey applicative laws.

If the class do, it also gain these methods for free:
- `lift1` - `lift30` - factory for augmenting callable to accept and return values wrapped in `Applicative` context. Supports up to 30 fixed arguments.
- `lift` - generic version of lift above. The generic version has worse type safety checks.
- `sequence` - operation to combine multiple `Applicative` instances into single `Applicative` instances containg multiple values (in `ArrayList`).
- `traverse` - similar to `sequence` allowing transformation of the values along the way.
- `ap` - applies single value to callback in context of `Applicative`

## Monad

`Monad` uses `Applicative` trait and defines one extra abstract method:
- `flatMap` - chains operation on `Monad`

Class that mixin this trait needs to implement all methods from `Monad` and `Applicative` as well except `Applicative::ap` method,
that is already implemented in `Monad`.

## Iterable

Iterable defines only 2 abstract methods:
- `reduce` - for reducing structure to single value
- `getIterator` - a method compatible with `\IteratorAggregate` iterface

From those 2 simple methods we can derive lots of useful methods for free:
- `mfold` - left folding via `\Bonami\Collection\Monoid\Monoid` instance
- `sum` - transforms item to integer / float and then sums them together
- `find` - finds item by predicate
- `head` - gets very first item if present 
- `last` - gets very last item if present
- `min` - gets minimal item by comparator
- `max`- gets maximal item by comparator
- `exists` - checks if at least one element matches the predicate
- `all` - checks if all elements matches the predicate 
- `each` - executes side effect on each element
- `tap` - executes side effect on each element and return self
- `toArray` - converts structure to `array`
- `toList` - converts structure to `\Bonami\Collection\ArrayList`
- `count` - counts number of elements
- `isEmpty` - checks if the structure is empty
- `isNotEmpty` - checks if the structure is not empty

## Overriding methods

Sometimes concrete type-class instance (the class that uses type-class trait) can have more optimal version of
"derived" method. Mixing trait into does not forbid overriding it with more optimal version.

## Caveats of traits

They do surprisingly good job for us, but they have one big drawback: they are not types themselves.

This mean we cannot use them anywhere in code in typehints and we cannot check object instances if 
they are `instanceof` some concrete trait.  
