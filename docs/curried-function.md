# CurriedFunction

Represents single argument function. Provides static factories to create curried versions of multi argument functions.

## Currying

The concept is simple - it is transforming function taking multiple arguments into sequence of functions each taking single argument.

```php
$greeter = fn (string $greeting, string $name): string => "{$greeting} {$name}!";

$curriedGreeter = CurriedFunction::curry2($greeter);

$englishGreeter = $curriedGreeter("Hello"); // partial apply
echo $englishGreeter("John"); // Hello John!
echo $englishGreeter("Paul"); // Hello Paul!

$spanishGreeter = $curriedGreeter("Hola"); // partial apply
echo $spanishGreeter("Diego"); // Hola Diego!
```

You might wonder, where this can be useful. We can leverage this everywhere, where we expect single argument closure but we have two arguments function.

E.g.

```php
$strings = ArrayList::of('a', 'b');
$times1 = ArrayList::of(1, 2, 3);
$times2 = ArrayList::of(4);

$strRepeat = CurriedFunction::self::curry2(str_repeat(...));

$partiallyApplied = $strings->map($strRepeat);

// ArrayList::fromIterable(['a', 'aa', 'aaa', 'b', 'bb', 'bbb']
$fullyApplied1 = $partiallyApplied->flatMap(fn ($pa) => $times1->map($pa));

// ArrayList::fromIterable(['aaaa', 'bbbb']
$fullyApplied2 = $partiallyApplied->flatMap(fn ($pa) => $times2->map($pa));
```

API provides factories to curry functions with up to 30 arguments. Reason to have factories by number of arguments is to provide type safety. 
If you use phpstan (which we strongly recommend), it can check all input parameters.

## Composition

Single argument functions are easy to compose. We provide `map` function for this purpose.

```php
$plusOne = fn (int $i): int => $i + 1;
$timesTwo = fn (int $i): int => $i * 2;

$plusOneThenTimesTwo = CurriedFunction::of($plusOne)->map($timesTwo);
$plusOneThenTimesTwo(5); // 12
```

`$f->map($g)` is equivalent of `g ∘ f` or `g(f(x))` in mathematics notation.

```php
$composed1 = $f->map($g);
$composed2 = fn ($x) => $g($f($x));
// $composed1 and $composed2 are equivalent
```

With phpstan, it also checks input / output arguments and creates typed version of composed function.
```php
function foo(string $s): int {
    return strlen($s);
} 

/**
* @param int $i
* @return array<string>
 */
function bar(int $i): array {
    return array_fill(0, $i, 'a');
}
// accepts string and returns array<string>.
// composed type is CurriedFunction<string, array<string>> which is equivalent of callable(string): array<string>
$foobar = CurriedFunction::of(foo(...))->map(CurriedFunction::of(bar(...)));
$foobar('abc'); // ['a', 'a', 'a']
```
