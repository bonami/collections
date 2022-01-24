# Option

> aka how to avoid billion dollar mistake by using `Option` (we are looking in your direction, `null`!)

`Option` type encapsulates value, which may or may not exist. If you are not familiar with concept of `Option` (also called `Maybe` in some languages), think of `ArrayList` which is either empty or has single item inside.

Value which exists is represented in `some` instance, whereas missing one is `none`.

```php
use Bonami\Collection\Option;

$somethingToEat = Option::some("ice cream");
$nothingToSeeHere = Option::none();
```

The good thing is that we can operate on `some` & `none` the same way:

```php 
use Bonami\Collection\Option;

$somethingToEat = Option::some("ice cream");
$nothingToSeeHere = Option::none();

$iLikeToEat = fn (string $food): string => "I like to eat tasty {$food}!"; 

$somethingToEat->map($iLikeToEat); // Will map to string "I like to eat tasty ice cream!" wrapped in `some` instance
$nothingToSeeHere->map($iLikeToEat); // `none`, wont be mapped and will stay the same
``` 

We can use `Option` as better and more safe alternative to nullable values since handling of `null` may easily become cumbersome.

## Example

Imagine we have some (dummy) functions like this:

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
```

Classical way to combine these `with` null will look something like this:

```php
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

With `Option` we can do better:

```php
function printUserAgeById(int $id): void {
    print Option::fromNullable(getUserEmailById($id))
        ->flatMap(Option::fromNullable(getAgeByUserEmail(...))
        ->map(fn (int $age): string => "Age of user with id {$id} is {$age}")
        ->getOrElse("Dont know age of user with id {$id}");
}
```

Or we can design our methods to work with `Option` in a first place:
```php
/**
 * @param int $id
 * @return Option<string>
 */
function getUserEmailById(int $id): Option {
    $usersDb = [
        1 => "john@foobar.baz",
        2 => "paul@foobar.baz",
    ];
    return Option::fromNullable($usersDb[$id] ?? null);
} 
/**
 * @param string $email
 * @return Option<int>
 */
function getAgeByUserEmail(string $email): Option {
    $ageDb = [
        "john@foobar.baz" => 66,
        "diego@hola.esp" => 42,
    ];
    return Option::fromNullable($ageDb[$email] ?? null);
}
```

And then:

```php
function printUserAgeById(int $id): void {
    print getUserEmailById($id)
        ->flatMap(getAgeByUserEmail(...))
        ->map(fn (int $age): string => "Age of user with id {$id} is {$age}")
        ->getOrElse("Dont know age of user with id {$id}");
}
```

You can see that the example using `Option` allows us to sequence (chain) computations so that if 
any of intermediate steps yields `none`, the subsequent computations are simply ignored.
We hope you have a grasp of it, even though example is rather artificial ;-)

## Type classes

Option mixes `Applicative1`, `Monad1` and `Iterable1` traits. 
If you don't know, what [type-class](./type-classes.md) is, don't despair. It simply means,
that it has some common behaviour with other structures and that it has quite rich interface
of methods that it gets (from those traits).

In case you are a functional programming zealot, you'd like to hear that `Option` 
is a lawful monad (thus functor & applicative).
