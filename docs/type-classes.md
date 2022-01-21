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

## Applicative

Applicative defines abstract methods:
- `pure` - wraps single value into `Applicative` instance. Or more theoretically: pulls impure value into `Applicative` instance context.
- `ap` - applies single value to callback in context of `Applicative`
- `map` - maps over values in context of `Applicative`

Class that mixin this trait needs to implement them. It should implement them in a way, that they obey applicative laws.

If the class do, it also gain these methods for free:
- `lift1` - `lift30` - factory for augmenting callable to accept and return values wrapped in `Applicative` context. Supports up to 30 fixed arguments.
- `lift` - generic version of lift above. The generic version has worse type safety checks.
- `sequence` - operation to combine multiple `Applicative` instances into single `Applicative` instances containg multiple values (in `ArrayList`).
- `travese` - similar to `sequence` allowing transformation of the values along the way.

## Monad

`Monad` uses `Applicative` trait and defines one extra abstract method:
- `flatMap` - chains operation on `Monad`

Class that mixin this trait needs to implement all methods from `Monad` and `Applicative` as well except `Applicative::ap` method,
that is already implemented in `Monad`.

## Overriding methods

Sometimes concrete type-class instance (the class that uses type-class trait) can have more optimal version of
"derived" method. Mixing trait into does not forbid overriding it with more optimal version.
