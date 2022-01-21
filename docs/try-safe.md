# TrySafe

What [`Option`](./option.md) is for possible missing (nullable) values, `TrySafe` is for possible exceptions.

Traditional way of throwing exceptions and catching them somewhere may obscure that the code can fail. 
`TrySafe` is designed (among other things) to be explicit about it on type level.

## Example
Consider the following code:
```php
interface IntegerParser {
    public function parse(mixed $input): int;
}
```
For well-behaved inputs it will return `int` according to return type declaration.
Until we pass something that we cannot parse.

As you would guess, `parse` will throw some `Exception` when parsing fails. 
We can "improve" the code a little with explicit `@throws` php doc annotation,
but that's about it. Nothing forces us to somehow handle the exceptional case on client side.

Even typical static analysis don't care too much, because there is nothing like "checked exceptions"
(like in Java).

```php
$result = IntegerParser::parse("123") + IntegerParse::parse("foo");
```

Another problem is, that it fails only "sometime". 
Conditional failing is hell for debugging, it is easier to debug something,
that fails always if not handled correctly.

Let's see how `TrySafe` can help

```php
use Bonami\Collection\TrySafe;

interface IntegerParser {
    /**
     * @param mixed $input
     * @return TrySafe<int>
     */
    public function parse(mixed $input): TrySafe;
}
```

This time we know, that parsing can fail directly from signature. What's better, 
we cannot access the int directly without handling possible failure as well!

```php
$result = IntegerParser::parse("123")
    ->flatMap(fn (int $a) => IntegerParse::parse("foo")->map(fn (int $b) => $a + $b));
```

The example above does not look that much pretty at first glance 
(when we need to treat multiple instances of `TrySafe`).

Fortunately we have more ways of writing this. For example this way:
```php
use Bonami\Collection\ArrayList;
use Bonami\Collection\identity;

$result = ArrayList::of("123", "foo")
    ->flatMap(IntegerParse::parse(...))
    ->sum(identity());
```

Or this way:
```php
use Bonami\Collection\TrySafe;

$result = TrySafe::lift2(fn (int $a, int $b) => $a + $b)(
    IntegerParser::parse("123"),
    IntegerParser::parse("foo"),
);
```

## Recovery

We have already learned, that `TrySafe` can be used for chaining dependent operations that can fail (via `flatMap`).
How about having some fallback / recovery in the middle of that chain?

This is where `recover*` methods come in handy. Let's take a look at this example:

```php
/** @var TrySafe<int> */
$distance = $api
    ->findGps($query)
    ->recoverWith(fn (Throwable $ex): TrySafe => $backupApi->findGps($query))
    ->map(fn (Gps $gps) => $this->getDistance($home));
```

There are four `recover*` methods:
- `recover` - Use it to recover with value directly
- `recoverWith` - Use it to recover with value wrapped in `TrySafe`. That allows chaining multiple `failure` recoveries, likewise `flatMap` does for `success`.
- `recoverIf` - Same as `recover`, except it recovers failure only if passed predicate evaluates to true.
- `recoverWithIf` - Same as `recoverWith`, except it recovers failure only if passed predicate evaluates to true.

```php
/** @var TrySafe<int> */
$distance = $api
    ->findGps($query)
    ->recoverWithIf(
        fn (Throwable $ex) => $ex instanceof ConnectionFailure, // recovers only if first api is down
        fn (Throwable $ex): TrySafe => $backupApi->findGps($query),
    )
    ->recoverIf(
        fn (Throwable $ex) => $ex instanceof MalformedQuery, // rather the recovery it keeps as more specific failure
        fn (Throwable $ex) => throw new CannotGetGps("Query $query was malformed", 0, $ex),
    )
    ->map(fn (Gps $gps) => $this->getDistance($home));
```
