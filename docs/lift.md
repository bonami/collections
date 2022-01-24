# Lift operator

Lifting function into (applicative) context means transforming it so it can operate on values wrapped in that context.

## Example
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

// Our super useful method accepts string host, int port & returns uri string
$createUri = fn (string $host, int $port): string => "https://{$host}:{$port}";

// Unfortunately our string & int are wrapped in Option context, what now?
$hostOption = getConfig('host');
$portOption = getConfig('port');

// Lifting method will allow to pass values wrapped in Option!
$createUriLifted = Option::lift2($createUri);

// Return value is wrapped in Option too
print $createUriLifted($hostOption, $portOption); // Option::some("https://domain.tld:8080")
```

Please note, that `None` behaves very greedily here - if any of arguments passed into
lifted function is `None` the result will be `None` too.
Lifting function into `TrySafe` context works analogously.

As we said elsewhere `Option` & `ArrayList` are very look-alike.
Both can represent that value is missing or present and on top of that `ArrayList`
can represent that there is more than one value. 
We can do some pretty sick tricks using function lifted in `ArrayList` (`LazyList`) context:

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

This is not coincidence - `Option::none()`, `ArrayList::fromEmpty()`, `LazyList::fromEmpty()`, `TrySafe::failure($ex)`, `Either::left($error)` 
they all create an instance expressing nonexistence of value and are analogous to zero in context of multiplication 
(empty set in cartesian product for these type classes actually).

All this "common" behavior is similar, because they are all Monads and uses `Applicative` and `Monad` traits.
For more information see [type classes](./type-classes.md) doc
