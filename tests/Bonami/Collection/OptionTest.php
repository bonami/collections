<?php

declare(strict_types=1);

namespace Bonami\Collection;

use Bonami\Collection\Exception\ValueIsNotPresentException;
use PHPUnit\Framework\TestCase;
use Throwable;

class OptionTest extends TestCase
{
    public function testCreate(): void
    {
        $none = Option::none();
        self::assertFalse($none->isDefined());
        self::assertTrue($none->isEmpty());

        $some = Option::some(666);
        self::assertTrue($some->isDefined());
        self::assertFalse($some->isEmpty());

        $fromNull = Option::of(null);
        self::assertTrue($fromNull->isDefined());
        self::assertFalse($fromNull->isEmpty());
    }

    public function testCreateFromNullable(): void
    {
        self::assertTrue(Option::fromNullable('Look, I exist')->isDefined());
        self::assertFalse(Option::fromNullable(null)->isDefined());
    }

    public function testLift(): void
    {
        $none = Option::none();
        $xOpt = Option::some(1);
        $yOpt = Option::some(4);

        $plus = static function (int $x, int $y): int {
            return $x + $y;
        };

        $this->equals(
            Option::some(5),
            Option::lift($plus)($xOpt, $yOpt)
        );
        $this->equals(
            $none,
            Option::lift($plus)($xOpt, $none)
        );
    }

    public function testLiftN(): void
    {
        self::assertEquals(Option::some(42), Option::lift1(static fn (int $a): int => $a)(Option::some(42)));
        self::assertEquals(
            Option::some(708),
            Option::lift2(static fn (int $a, int $b): int => $a + $b)(Option::some(42), Option::some(666))
        );
    }

    public function testMap(): void
    {
        $mapper = static function (string $s): string {
            return sprintf('Hello %s', $s);
        };
        $mapperToNull = static function (string $s) {
            return null;
        };

        $this->equals(
            Option::some('Hello world'),
            Option::some('world')->map($mapper)
        );
        $this->equals(
            Option::none(),
            Option::none()->map($mapper)
        );

        $this->equals(
            Option::some(null),
            Option::some('world')->map($mapperToNull)
        );
        $this->equals(
            Option::none(),
            Option::none()->map($mapperToNull)
        );
    }

    public function testFlatMap(): void
    {
        /** @phpstan-var callable(string): Option<string> */
        $mapperToSome = static function (string $s): Option {
            return Option::some(sprintf('Hello %s', $s));
        };
        /** @phpstan-var callable(string): Option<string> */
        $mapperToNone = static function (string $s): Option {
            return Option::none();
        };

        $this->equals(
            Option::some('Hello world'),
            Option::some('world')->flatMap($mapperToSome)
        );
        $this->equals(
            Option::none(),
            Option::none()->flatMap($mapperToSome)
        );
        $this->equals(
            Option::none(),
            Option::some('world')->flatMap($mapperToNone)
        );
        $this->equals(
            Option::none(),
            Option::none()->flatMap($mapperToNone)
        );
    }

    public function testFilter(): void
    {
        $some = Option::some('Hello world');

        $falsyPredicate = static function (): bool {
            return false;
        };

        $this->equals(
            $some,
            $some->filter(tautology())
        );
        $this->equals(
            Option::none(),
            Option::none()->filter(tautology())
        );
        $this->equals(
            Option::none(),
            $some->filter($falsyPredicate)
        );
        $this->equals(
            Option::none(),
            Option::none()->filter($falsyPredicate)
        );
    }

    public function testExists(): void
    {
        $some = Option::some('Hello world');

        $falsyPredicate = static function (): bool {
            return false;
        };

        $this->equals(
            true,
            $some->exists(tautology())
        );
        $this->equals(
            false,
            Option::none()->exists(tautology())
        );
        $this->equals(
            false,
            $some->exists($falsyPredicate)
        );
        $this->equals(
            false,
            Option::none()->exists($falsyPredicate)
        );
    }

    public function testGetUnsafe(): void
    {
        $val = 'Hello world';
        $some = Option::some($val);

        self::assertEquals($val, $some->getUnsafe());
        try {
            Option::none()->getUnsafe();
            self::fail('Calling get method or None must throw');
        } catch (Throwable $e) {
            self::assertInstanceOf(ValueIsNotPresentException::class, $e);
        }
    }

    public function testGetOrElse(): void
    {
        $val = 'Hello world';
        $some = Option::some($val);

        $else = 'Embrace the dark lord';
        self::assertEquals($val, $some->getOrElse($else));
        self::assertEquals($else, Option::none()->getOrElse($else));
    }

    public function testToTrySafe(): void
    {
        $val = 'Hello world';
        self::assertEquals($val, Option::some($val)->toTrySafe()->getUnsafe());
        self::assertInstanceOf(ValueIsNotPresentException::class, Option::none()->toTrySafe()->getFailureUnsafe());
    }

    public function testToEither(): void
    {
        $val = 'Hello world';
        self::assertEquals($val, Option::some($val)->toEither(42)->getRightUnsafe());
        self::assertEquals(42, Option::none()->toEither(42)->getLeftUnsafe());
    }

    public function testToList(): void
    {
        $val = 'Hello world';
        self::assertEquals([$val], Option::some($val)->toList()->toArray());
        self::assertEquals([], Option::none()->toList()->toArray());
    }

    public function testToArray(): void
    {
        $val = 'Hello world';
        self::assertEquals([$val], Option::some($val)->toArray());
        self::assertEquals([], Option::none()->toArray());
    }

    public function testIterator(): void
    {
        $val = 'Hello world';
        $some = Option::some($val);

        self::assertEquals([$val], iterator_to_array($some->getIterator(), false));
        self::assertEquals([], iterator_to_array(Option::none()->getIterator(), false));
    }

    public function testReduce(): void
    {
        $reducer = static function (int $reduction, int $val): int {
            return $reduction + $val;
        };
        $initialReduction = 4;

        self::assertEquals(
            670,
            Option::some(666)->reduce($reducer, $initialReduction)
        );
        self::assertEquals(
            $initialReduction,
            Option::none()->reduce($reducer, $initialReduction)
        );
    }

    public function testEach(): void
    {
        $accumulator = 0;
        $accumulate = static function (int $i) use (&$accumulator): void {
            $accumulator += $i;
        };

        Option::none()->each($accumulate);
        self::assertEquals(0, $accumulator);

        Option::some(5)->each($accumulate);
        self::assertEquals(5, $accumulator);
    }

    public function testTap(): void
    {
        $some = Option::some(1);
        $none = Option::none();
        $accumulated = 0;

        $accumulate = static function (int $i) use (&$accumulated): void {
            $accumulated += $i;
        };

        self::assertSame($none, $none->tap($accumulate));
        self::assertEquals(0, $accumulated);

        self::assertSame($some, $some->tap($accumulate));
        self::assertEquals(1, $accumulated);
    }

    public function testTapNone(): void
    {
        $some = Option::some(1);
        $none = Option::none();
        $counter = 0;

        $increment = static function () use (&$counter): void {
            $counter++;
        };

        self::assertSame($some, $some->tapNone($increment));
        self::assertEquals(0, $counter);

        self::assertSame($none, $none->tapNone($increment));
        self::assertEquals(1, $counter);
    }

    public function testAp(): void
    {
        $purePlus = Option::of(CurriedFunction::curry2(static function (int $x, int $y): int {
            return $x + $y;
        }));
        /** @var Option<int> $noneInt */
        $noneInt = Option::none();
        $one = Option::some(1);
        $two = Option::some(2);
        $three = Option::some(3);

        /** @var Option<CurriedFunction<int, CurriedFunction<int, int>>> $noneClosure */
        $noneClosure = Option::none();

        $this->equals(Option::ap(Option::ap($purePlus, $one), $two), $three);
        $this->equals(Option::ap(Option::ap($purePlus, $one), $noneInt), $noneInt);
        $this->equals(Option::ap(Option::ap($purePlus, $noneInt), $one), $noneInt);
        $this->equals(Option::ap(Option::ap($purePlus, $noneInt), $noneInt), $noneInt);
        $this->equals(Option::ap(Option::ap($noneClosure, $one), $two), $noneInt);
    }

    public function testProduct(): void
    {
        self::assertEquals(Option::some([1, 'a']), Option::product(Option::some(1), Option::some('a')));
        self::assertEquals(Option::none(), Option::product(Option::some(1), Option::none()));
    }

    public function testTraverse(): void
    {
        $iterable = [
            Option::some(42),
            Option::some(666),
        ];

        $iterableWithNone = [
            Option::some(42),
            Option::none(),
        ];

        /** @phpstan-var array<Option<int>> $emptyIterable */
        $emptyIterable = [];

        self::assertEquals([42, 666], Option::sequence($iterable)->getUnsafe()->toArray());
        self::assertEquals([], Option::sequence($emptyIterable)->getUnsafe()->toArray());
        self::assertSame(Option::none(), Option::sequence($iterableWithNone));

        $numbersLowerThan10 = [1, 2, 3, 7, 9];

        /** @phpstan-var callable(int): Option<int> */
        $wrapLowerThan10 = static function (int $int): Option {
            return $int < 10 ? Option::some($int) : Option::none();
        };

        /** @phpstan-var callable(int): Option<int> */
        $wrapLowerThan9 = static function (int $int): Option {
            return $int < 9 ? Option::some($int) : Option::none();
        };

        self::assertEquals(
            $numbersLowerThan10,
            Option::traverse($numbersLowerThan10, $wrapLowerThan10)->getUnsafe()->toArray()
        );
        self::assertSame(
            Option::none(),
            Option::traverse($numbersLowerThan10, $wrapLowerThan9)
        );
    }

    public function testSequence(): void
    {
        $iterable = [
            Option::some(42),
            Option::some(666),
        ];

        $iterableWithNone = [
            Option::some(42),
            Option::none(),
        ];

        /** @phpstan-var array<Option<int>> $emptyIterable */
        $emptyIterable = [];

        self::assertEquals([42, 666], Option::sequence($iterable)->getUnsafe()->toArray());
        self::assertEquals([], Option::sequence($emptyIterable)->getUnsafe()->toArray());
        self::assertSame(Option::none(), Option::sequence($iterableWithNone));
    }

    public function testOrElse(): void
    {
        $some42 = Option::some(5);
        $some666 = Option::some(666);
        $none = Option::none();

        $this->equals($some42, $some42->orElse($some666));
        $this->equals($some666, $none->orElse($some666));
    }

    public function testResolveSome(): void
    {
        $handleSomeSpy = createInvokableSpy();
        $handleNoneSpy = createInvokableSpy();

        Option::some(666)
            ->resolve($handleNoneSpy, $handleSomeSpy);

        self::assertCount(1, $handleSomeSpy->getCalls());
        self::assertCount(0, $handleNoneSpy->getCalls());
        self::assertEquals([[666]], $handleSomeSpy->getCalls());
    }

    public function testResolveNone(): void
    {
        $handleSomeSpy = createInvokableSpy();
        $handleNoneSpy = createInvokableSpy();

        Option::none()
            ->resolve($handleNoneSpy, $handleSomeSpy);

        self::assertCount(0, $handleSomeSpy->getCalls());
        self::assertCount(1, $handleNoneSpy->getCalls());
        self::assertSame([[]], $handleNoneSpy->getCalls());
    }

    public function testLaws(): void
    {

        $assertEquals = function ($a, $b): void {
            $this->equals($a, $b);
        };
        $optionEquals = static function (Option $a, Option $b): bool {
            return $a->equals($b);
        };
        $ap = static function (Option $a, Option $b): Option {
            // @phpstan-ignore-next-line
            return Option::ap($a, $b);
        };
        $pure = static function ($value): Option {
            return Option::of($value);
        };

        $someOne = Option::some(1);
        $someTwo = Option::some(2);
        $someThree = Option::some(3);
        $none = Option::none();

        $plus2 = CurriedFunction::of(static function (int $x): int {
            return $x + 2;
        });
        $multiple2 = CurriedFunction::of(static function (int $x): int {
            return $x * 2;
        });

        testEqualsReflexivity($assertEquals, $optionEquals, $someOne);
        testEqualsReflexivity($assertEquals, $optionEquals, $none);

        testEqualsSymmetry($assertEquals, $optionEquals, $someOne, $someOne);
        testEqualsSymmetry($assertEquals, $optionEquals, $someOne, $someTwo);
        testEqualsSymmetry($assertEquals, $optionEquals, $someOne, $none);
        testEqualsSymmetry($assertEquals, $optionEquals, $none, $none);

        testEqualsTransitivity($assertEquals, $optionEquals, $someOne, $someOne, $someOne);
        testEqualsTransitivity($assertEquals, $optionEquals, $someOne, $someTwo, $someThree);
        testEqualsTransitivity($assertEquals, $optionEquals, $someOne, $none, $someThree);

        testFunctorIdentity($assertEquals, $someOne);
        testFunctorIdentity($assertEquals, $none);

        testFunctorComposition($assertEquals, $someOne, $plus2, $multiple2);
        testFunctorComposition($assertEquals, $none, $plus2, $multiple2);

        testApplicativeIdentity($assertEquals, $ap, $pure, $someOne);
        testApplicativeIdentity($assertEquals, $ap, $pure, $none);

        testApplicativeHomomorphism($assertEquals, $ap, $pure, 666, $multiple2);
        testApplicativeHomomorphism($assertEquals, $ap, $pure, 666, $multiple2);

        testApplicativeComposition($assertEquals, $ap, $pure, $someOne, $pure($plus2), $pure($multiple2));
        testApplicativeComposition($assertEquals, $ap, $pure, $none, $pure($plus2), $pure($multiple2));
        testApplicativeComposition($assertEquals, $ap, $pure, $someOne, $none, $pure($multiple2));
        testApplicativeComposition($assertEquals, $ap, $pure, $none, $pure($plus2), $none);

        testApplicativeInterchange($assertEquals, $ap, $pure, 666, $pure($plus2));
        testApplicativeInterchange($assertEquals, $ap, $pure, 666, $none);
    }

    /**
     * @template A
     * @template B
     *
     * @phpstan-param A $a
     * @phpstan-param B $b
     *
     * @phpstan-return void
     */
    private function equals($a, $b): void
    {
        if ($a instanceof Option && $b instanceof Option) {
            self::assertTrue($a->equals($b));
        } else {
            self::assertEquals($a, $b);
        }
    }
}
