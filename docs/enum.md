# Enum

Enum is type with values from finite closed set (think of it as boolean type, which has exactly two possible values - true & false).

## Usage

We can define our Enum extending abstract Enum class. All possible values are defined via constants. Constant values can be type of string or int
```php
use Bonami\Collection\Enum;

class Color extends Enum {

	const RED = "RED";
	const BLUE = "BLUE";
	const GREEN = "GREEN";
}
```

We can get instance of Enum by using `create` method. 
```php
$redColor = Color::create(Color::RED);
```

For more convinient instancing, we can add static constructors that will simplify Enum usage.
```php
use Bonami\Collection\Enum;

class Color extends Enum {

	const RED = "RED";
	const BLUE = "BLUE";
	const GREEN = "GREEN";
	
	public static function RED(): Color {
		return self::create(self::RED);
	}

	public static function BLUE(): Color {
		return self::create(self::BLUE);
	}

	public static function GREEN(): Color {
		return self::create(self::GREEN);
	}
}

$redColor = Color::RED();
```

All instances of same Enum value are equal. They share same reference.
```php
// bool(true)
var_dump(Color::RED() === Color::RED());
```

### Check existence of element
 ```php
// bool(false)
var_dump(Color::exists("BLACK"));

// bool(true)
var_dump(Color::exists("BLUE"));
 ```

### List and Map support
**TODO** doc links

You can get all instances as List or Map.

#### List
 ```php
$lowercaseEnums = Color::instanceList()
	->map(fn(Color $color): string => strtolower($color->getValue()))
	->join(", ");

// string(16) "red, blue, green"
var_dump($lowercaseEnums);
```

#### Map
```php
$colorReverseMap = Color::instanceMap()
	->mapValues(fn(Color $color, string $key): string => strrev($color->getValue()))
	->mapKeys(fn(string $color): string => strtolower($color));

// bool(true)
var_dump($colorReverseMap->get('blue')->isDefined());

// string(4) "EULB"
var_dump($colorReverseMap->get('blue')->getUnsafe());
```

####  List Complement

```php
$onlyRedColor = Color::getListComplement(Color::BLUE(), Color::GREEN());

// string(9) "RED"
var_dump($onlyRedColor->join(''));
```
