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
        self::assertInstanceOf(Option::class, $none);
        self::assertFalse($none->isDefined());

        $some = Option::some(666);
        self::assertInstanceOf(Option::class, $some);
        self::assertTrue($some->isDefined());

        $fromNull = Option::of(null);
        self::assertInstanceOf(Option::class, $fromNull);
        self::assertTrue($fromNull->isDefined());

        $fromNotNull = Option::some(666);
        self::assertInstanceOf(Option::class, $fromNotNull);
        self::assertTrue($fromNotNull->isDefined());
    }

    public function testCreateFromNullable(): void
    {
        self::assertTrue(Option::fromNullable("Look, I exist")->isDefined());
        self::assertFalse(Option::fromNullable(null)->isDefined());
    }

    public function testLift(): void
    {

        $none = Option::none();
        $xOpt = Option::some(1);
        $yOpt = Option::some(4);

        $plus = function (int $x, int $y): int {
            return $x + $y;
        };

        $this->equals(
            Option::some(5),
            (Option::lift($plus)($xOpt, $yOpt))
        );
        $this->equals(
            $none,
            (Option::lift($plus)($xOpt, $none))
        );
    }

    public function testMap(): void
    {
        $mapper = function (string $s): string {
            return "Hello {$s}";
        };
        $mapperToNull = function (string $s) {
            return null;
        };

        $this->equals(
            Option::some("Hello world"),
            Option::some("world")->map($mapper)
        );
        $this->equals(
            Option::none(),
            Option::none()->map($mapper)
        );

        $this->equals(
            Option::some(null),
            Option::some("world")->map($mapperToNull)
        );
        $this->equals(
            Option::none(),
            Option::none()->map($mapperToNull)
        );
    }

    public function testFlatMap(): void
    {
        $mapperToSome = function (string $s): Option {
            return Option::some("Hello {$s}");
        };
        $mapperToNone = function (string $s): Option {
            return Option::none();
        };

        $this->equals(
            Option::some("Hello world"),
            Option::some("world")->flatMap($mapperToSome)
        );
        $this->equals(
            Option::none(),
            Option::none()->flatMap($mapperToSome)
        );
        $this->equals(
            Option::none(),
            Option::some("world")->flatMap($mapperToNone)
        );
        $this->equals(
            Option::none(),
            Option::none()->flatMap($mapperToNone)
        );
    }

    public function testFilter(): void
    {
        $some = Option::some("Hello world");

        $falsyPredicate = function (): bool {
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

    public function testGetUnsafe(): void
    {
        $val = "Hello world";
        $some = Option::some($val);

        self::assertEquals($val, $some->getUnsafe());
        try {
            Option::none()->getUnsafe();
            self::fail("Calling get method or None must throw");
        } catch (Throwable $e) {
            self::assertInstanceOf(ValueIsNotPresentException::class, $e);
        }
    }

    public function testGetOrElse(): void
    {
        $val = "Hello world";
        $some = Option::some($val);

        $else = "Embrace the dark lord";
        self::assertEquals($val, $some->getOrElse($else));
        self::assertEquals($else, Option::none()->getOrElse($else));
    }

    public function testToTrySafe(): void
    {
        $val = "Hello world";
        self::assertEquals($val, Option::some($val)->toTrySafe()->getUnsafe());
        self::assertInstanceOf(ValueIsNotPresentException::class, Option::none()->toTrySafe()->getFailureUnsafe());
    }

    public function testIterator(): void
    {
        $val = "Hello world";
        $some = Option::some($val);

        self::assertEquals([$val], iterator_to_array($some->getIterator(), false));
        self::assertEquals([], iterator_to_array(Option::none()->getIterator(), false));
    }

    public function testReduce(): void
    {
        $reducer = function (int $reduction, int $val): int {
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

    public function testAp(): void
    {
        $plus = function (int $x, int $y): int {
            return $x + $y;
        };

        $purePlus = Option::of($plus);
        $none = Option::none();
        $one = Option::some(1);
        $two = Option::some(2);
        $three = Option::some(3);

        $this->equals($purePlus->ap($one)->ap($two), $three);
        $this->equals($purePlus->ap($one)->ap($none), $none);
        $this->equals($purePlus->ap($none)->ap($one), $none);
        $this->equals($purePlus->ap($none)->ap($none), $none);
        $this->equals($none->ap($one)->ap($two), $none);
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
        $wrapLowerThan10 = function (int $int): Option {
            return $int < 10 ? Option::some($int) : Option::none();
        };

        /** @phpstan-var callable(int): Option<int> */
        $wrapLowerThan9 = function (int $int): Option {
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
        $optionEquals = function (Option $a, Option $b): bool {
            return $a->equals($b);
        };
        $pure = function ($value): Option {
            return Option::of($value);
        };

        $someOne = Option::some(1);
        $someTwo = Option::some(2);
        $someThree = Option::some(3);
        $none = Option::none();

        $plus2 = function (int $x): int {
            return $x + 2;
        };
        $multiple2 = function (int $x): int {
            return $x * 2;
        };

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

        testApplicativeIdentity($assertEquals, $pure, $someOne);
        testApplicativeIdentity($assertEquals, $pure, $none);

        testApplicativeHomomorphism($assertEquals, $pure, 666, $multiple2);
        testApplicativeHomomorphism($assertEquals, $pure, 666, $multiple2);

        testApplicativeComposition($assertEquals, $pure, $someOne, $pure($plus2), $pure($multiple2));
        testApplicativeComposition($assertEquals, $pure, $none, $pure($plus2), $pure($multiple2));
        testApplicativeComposition($assertEquals, $pure, $someOne, $none, $pure($multiple2));
        testApplicativeComposition($assertEquals, $pure, $none, $pure($plus2), $none);

        testApplicativeInterchange($assertEquals, $pure, 666, $pure($plus2));
        testApplicativeInterchange($assertEquals, $pure, 666, $none);
    }

    /**
     * @template A
     * @template B
     * @param A $a
     * @param B $b
     *
     * @return void
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
