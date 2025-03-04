<?php

declare(strict_types=1);

namespace Bonami\Collection;

use Bonami\Collection\Exception\ValueIsNotPresentException;
use PHPUnit\Framework\TestCase;
use Throwable;

class EitherTest extends TestCase
{
    public function testCreate(): void
    {
        $left = Either::left('error');
        self::assertTrue($left->isLeft());

        $right = Either::right(666);
        self::assertTrue($right->isRight());

        $pure = Either::of(666);
        self::assertTrue($pure->isRight());
    }

    public function testLift(): void
    {

        $left = Either::left('error');
        $one = Either::right(1);
        $four = Either::right(4);

        $plus = static fn (int $x, int $y): int => $x + $y;

        $this->equals(
            Either::right(5),
            Either::lift($plus)($one, $four),
        );
        $this->equals(
            $left,
            Either::lift($plus)($one, $left),
        );
    }

    public function testLiftN(): void
    {
        self::assertEquals(Either::right(42), Either::lift1(static fn (int $a): int => $a)(Either::right(42)));
        self::assertEquals(
            Either::right(708),
            Either::lift2(static fn (int $a, int $b): int => $a + $b)(Either::right(42), Either::right(666)),
        );
        self::assertEquals(
            Either::left("fail"),
            Either::lift2(static fn (int $a, int $b): int => $a + $b)(Either::right(42), Either::left("fail")),
        );
    }

    public function testMap(): void
    {
        $greeter = static fn (string $s): string => sprintf('Hello %s', $s);

        $this->equals(
            Either::right('Hello world'),
            Either::right('world')->map($greeter),
        );
        $this->equals(
            Either::left('error'),
            Either::left('error')->map($greeter),
        );
    }

    public function testMapLeft(): void
    {
        $greeter = static fn (string $s): string => sprintf('Hello %s', $s);

        $this->equals(
            Either::right('world'),
            Either::right('world')->mapLeft($greeter),
        );
        $this->equals(
            Either::left('Hello error'),
            Either::left('error')->mapLeft($greeter),
        );
    }

    public function testFlatMap(): void
    {
        $politeGreeter = static fn (string $s): Either => Either::right(sprintf('Hello %s', $s));

        $failingGreeter = static fn (string $s): Either => Either::left('No manners');

        $this->equals(
            Either::right('Hello world'),
            Either::right('world')->flatMap($politeGreeter),
        );
        $this->equals(
            Either::left('No manners'),
            Either::right('world')->flatMap($failingGreeter),
        );
        $this->equals(
            Either::left('error'),
            Either::left('error')->flatMap($politeGreeter),
        );
    }

    public function testFlatMapLeft(): void
    {
        $this->equals(
            Either::right('world'),
            Either::right('world')->flatMapLeft(static fn ($s) => Either::right("will not be called")),
        );
        $this->equals(
            Either::left('Fail'),
            Either::left('error')->flatMapLeft(static fn ($s): Either => Either::left('Fail')),
        );
        $this->equals(
            Either::right('Hello error'),
            Either::left('error')->flatMapLeft(static fn ($s): Either => Either::right(sprintf('Hello %s', $s))),
        );
    }

    public function testExists(): void
    {
        $right = Either::right('Hello world');
        $left = Either::left('error');

        $falsyPredicate = static fn (): bool => false;

        $this->equals(
            true,
            $right->exists(tautology()),
        );
        $this->equals(
            false,
            $left->exists(tautology()),
        );
        $this->equals(
            false,
            $right->exists($falsyPredicate),
        );
        $this->equals(
            false,
            $left->exists($falsyPredicate),
        );
    }

    public function testGetRightUnsafe(): void
    {
        $val = 'Hello world';
        $right = Either::right($val);

        self::assertEquals($val, $right->getRightUnsafe());
        try {
            Either::left('error')->getRightUnsafe();
            self::fail('Calling get method or None must throw');
        } catch (Throwable $e) {
            self::assertInstanceOf(ValueIsNotPresentException::class, $e);
        }
    }

    public function testGetLeftUnsafe(): void
    {
        $val = 'error';
        $left = Either::left($val);

        self::assertEquals($val, $left->getLeftUnsafe());
        try {
            Either::right('Hello world')->getLeftUnsafe();
            self::fail('Calling get method or None must throw');
        } catch (Throwable $e) {
            self::assertInstanceOf(ValueIsNotPresentException::class, $e);
        }
    }

    public function testGetOrElse(): void
    {
        $val = 'Hello world';
        $some = Either::right($val);

        $else = 'Embrace the dark lord';
        self::assertEquals($val, $some->getOrElse($else));
        self::assertEquals($else, Either::left(666)->getOrElse($else));
    }

    public function testToTrySafe(): void
    {
        $val = 'Hello world';
        self::assertEquals($val, Either::right($val)->toTrySafe()->getUnsafe());
        self::assertInstanceOf(ValueIsNotPresentException::class, Either::left(666)->toTrySafe()->getFailureUnsafe());
    }

    public function testIterator(): void
    {
        $val = 'Hello world';
        $some = Either::right($val);

        self::assertEquals([$val], iterator_to_array($some->getIterator(), false));
        self::assertEquals([], iterator_to_array(Either::left(666)->getIterator(), false));
    }

    public function testReduce(): void
    {
        $sum = static fn (int $a, int $b): int => $a + $b;
        $init = 4;

        self::assertEquals(
            42,
            Either::right(38)->reduce($sum, $init),
        );
        self::assertEquals(
            $init,
            Either::left(666)->reduce($sum, $init),
        );
    }

    public function testEach(): void
    {
        $accumulator = 0;
        $accumulate = static function (int $i) use (&$accumulator): void {
            $accumulator += $i;
        };

        Either::left(666)->each($accumulate);
        self::assertEquals(0, $accumulator);

        Either::right(42)->each($accumulate);
        self::assertEquals(42, $accumulator);
    }

    public function testTap(): void
    {
        $right = Either::right(42);
        $left = Either::left(666);
        $accumulated = 0;

        $accumulate = static function (int $i) use (&$accumulated): void {
            $accumulated += $i;
        };

        self::assertSame($left, $left->tap($accumulate));
        self::assertSame(666, $left->getLeftUnsafe());
        self::assertEquals(0, $accumulated);

        self::assertSame($right, $right->tap($accumulate));
        self::assertSame(42, $right->getRightUnsafe());
        self::assertEquals(42, $accumulated);
    }

    public function testTapLeft(): void
    {
        $right = Either::right(42);
        $left = Either::left(666);
        $accumulated = 0;

        $accumulate = static function (int $i) use (&$accumulated): void {
            $accumulated += $i;
        };

        self::assertSame($left, $left->tapLeft($accumulate));
        self::assertSame(666, $left->getLeftUnsafe());
        self::assertEquals(666, $accumulated);

        self::assertSame($right, $right->tapLeft($accumulate));
        self::assertSame(42, $right->getRightUnsafe());
        self::assertEquals(666, $accumulated);
    }

    public function testAp(): void
    {
        $plus = CurriedFunction::curry2(static fn (int $x, int $y): int => $x + $y);
        /** @var Either<string, CurriedFunction<int, CurriedFunction<int, int>>> */
        $leftOp = Either::left('undefined operation');

        $purePlus = Either::of($plus);
        /** @var Either<string, int> */
        $left = Either::left('missing value');
        /** @var Either<string, int> */
        $one = Either::right(1);
        /** @var Either<string, int> */
        $two = Either::right(2);
        /** @var Either<string, int> */
        $three = Either::right(3);

        $this->equals(Either::ap(Either::ap($purePlus, $one), $two), $three);
        $this->equals(Either::ap(Either::ap($purePlus, $one), $left), $left);
        $this->equals(Either::ap(Either::ap($purePlus, $left), $one), $left);
        $this->equals(Either::ap(Either::ap($purePlus, $left), $left), $left);
        $this->equals(Either::ap(Either::ap($leftOp, $one), $two), $leftOp);
    }

    public function testProduct(): void
    {
        self::assertEquals(Either::right([1, 'a']), Either::product(Either::right(1), Either::right('a')));
        self::assertEquals(Either::left('fuu'), Either::product(Either::right(1), Either::left('fuu')));
    }

    public function testTraverse(): void
    {
        $iterable = [
            Either::right(42),
            Either::right(666),
        ];

        $iterableWithFails = [
            Either::left('error1'),
            Either::left('error2'),
            Either::right(42),
        ];

        /** @phpstan-var array<Either<string, int>> $emptyIterable */
        $emptyIterable = [];

        self::assertEquals([42, 666], Either::sequence($iterable)->getRightUnsafe()->toArray());
        self::assertEquals([], Either::sequence($emptyIterable)->getRightUnsafe()->toArray());
        self::assertEquals(Either::left('error1'), Either::sequence($iterableWithFails));

        $numbersLowerThan10 = [1, 2, 3, 7, 9];

        $wrapLowerThan10 = static fn (int $int): Either => $int < 10
            ? Either::right($int)
            : Either::left('higher then ten');

        $wrapLowerThan9 = static fn (int $int): Either => $int < 9
            ? Either::right($int)
            : Either::left('higher then nine');

        self::assertEquals(
            $numbersLowerThan10,
            Either::traverse($numbersLowerThan10, $wrapLowerThan10)->getRightUnsafe()->toArray(),
        );
        self::assertEquals(
            Either::left('higher then nine'),
            Either::traverse($numbersLowerThan10, $wrapLowerThan9),
        );
    }

    public function testSequence(): void
    {
        $iterable = [
            Either::right(42),
            Either::right(666),
        ];

        $iterableWithFails = [
            Either::right(42),
            Either::left('error2'),
            Either::left('error1'),
        ];

        /** @phpstan-var array<Either<string, int>> $emptyIterable */
        $emptyIterable = [];

        self::assertEquals([42, 666], Either::sequence($iterable)->getRightUnsafe()->toArray());
        self::assertEquals([], Either::sequence($emptyIterable)->getRightUnsafe()->toArray());
        self::assertEquals(Either::left('error2'), Either::sequence($iterableWithFails));
    }

    public function testOrElse(): void
    {
        $right42 = Either::right(5);
        $right666 = Either::right(666);
        $error = Either::left('error');

        $this->equals($right42, $right42->orElse($right666));
        $this->equals($right666, $error->orElse($right666));
    }

    public function testResolveRight(): void
    {
        $handleRightSpy = createInvokableSpy();
        $handleLeftSpy = createInvokableSpy();

        Either::right(42)
            ->resolve($handleLeftSpy, $handleRightSpy);

        self::assertCount(1, $handleRightSpy->getCalls());
        self::assertCount(0, $handleLeftSpy->getCalls());
        self::assertEquals([[42]], $handleRightSpy->getCalls());
    }

    public function testResolveLeft(): void
    {
        $handleRightSpy = createInvokableSpy();
        $handleLeftSpy = createInvokableSpy();

        Either::left(666)
            ->resolve($handleLeftSpy, $handleRightSpy);

        self::assertCount(0, $handleRightSpy->getCalls());
        self::assertCount(1, $handleLeftSpy->getCalls());
        self::assertSame([[666]], $handleLeftSpy->getCalls());
    }

    public function testLaws(): void
    {
        $assertEquals = function ($a, $b): void {
            $this->equals($a, $b);
        };
        $eitherEquals = static fn (Either $a, Either $b): bool => $a->equals($b);
        $ap = Either::ap(...);
        $pure = Either::pure(...);

        $rightOne = Either::right(1);
        $rightTwo = Either::right(2);
        $rightThree = Either::right(3);
        $error = Either::left('error');

        $plus2 = CurriedFunction::of(static fn (int $x): int => $x + 2);
        $multiple2 = CurriedFunction::of(static fn (int $x): int => $x * 2);

        testEqualsReflexivity($assertEquals, $eitherEquals, $rightOne);
        testEqualsReflexivity($assertEquals, $eitherEquals, $error);

        testEqualsSymmetry($assertEquals, $eitherEquals, $rightOne, $rightOne);
        testEqualsSymmetry($assertEquals, $eitherEquals, $rightOne, $rightTwo);
        testEqualsSymmetry($assertEquals, $eitherEquals, $rightOne, $error);
        testEqualsSymmetry($assertEquals, $eitherEquals, $error, $error);

        testEqualsTransitivity($assertEquals, $eitherEquals, $rightOne, $rightOne, $rightOne);
        testEqualsTransitivity($assertEquals, $eitherEquals, $rightOne, $rightTwo, $rightThree);
        testEqualsTransitivity($assertEquals, $eitherEquals, $rightOne, $error, $rightThree);

        testFunctorIdentity($assertEquals, $rightOne);
        testFunctorIdentity($assertEquals, $error);

        testFunctorComposition($assertEquals, $rightOne, $plus2, $multiple2);
        testFunctorComposition($assertEquals, $error, $plus2, $multiple2);

        testApplicativeIdentity($assertEquals, $ap, $pure, $rightOne);
        testApplicativeIdentity($assertEquals, $ap, $pure, $error);

        testApplicativeHomomorphism($assertEquals, $ap, $pure, 666, $multiple2);
        testApplicativeHomomorphism($assertEquals, $ap, $pure, 666, $multiple2);

        testApplicativeComposition($assertEquals, $ap, $pure, $rightOne, $pure($plus2), $pure($multiple2));
        testApplicativeComposition($assertEquals, $ap, $pure, $error, $pure($plus2), $pure($multiple2));
        testApplicativeComposition($assertEquals, $ap, $pure, $rightOne, $error, $pure($multiple2));
        testApplicativeComposition($assertEquals, $ap, $pure, $error, $pure($plus2), $error);

        testApplicativeInterchange($assertEquals, $ap, $pure, 666, $pure($plus2));
        testApplicativeInterchange($assertEquals, $ap, $pure, 666, $error);
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
        if ($a instanceof Either && $b instanceof Either) {
            self::assertTrue($a->equals($b));
        } else {
            self::assertEquals($a, $b);
        }
    }
}
