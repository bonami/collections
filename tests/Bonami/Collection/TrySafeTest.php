<?php

declare(strict_types=1);

namespace Bonami\Collection;

use Bonami\Collection\Hash\IHashable;
use Exception;
use PHPUnit\Framework\TestCase;
use Throwable;

class TrySafeTest extends TestCase
{

    public function testCreate(): void
    {
        $trySafeFromScalar = TrySafe::of(666);
        self::assertInstanceOf(TrySafe::class, $trySafeFromScalar);
        self::assertTrue($trySafeFromScalar->isSuccess());

        $trySafeFromException = TrySafe::of(new Exception("Exception can act as success value"));
        self::assertInstanceOf(TrySafe::class, $trySafeFromException);
        self::assertTrue($trySafeFromScalar->isSuccess());
    }

    public function testCreateFromCallable(): void
    {
        $returnValueCallable = function () {
            return 666;
        };
        $throwCallable = function () {
            throw new Exception();
        };

        self::assertTrue(TrySafe::fromCallable($returnValueCallable)->isSuccess());
        self::assertTrue(TrySafe::fromCallable($throwCallable)->isFailure());
    }

    public function testLift(): void
    {
        $failure = $this->createFailure();
        $xSuccess = TrySafe::success(1);
        $ySuccess = TrySafe::success(4);

        $plus = function (int $x, int $y): int {
            return $x + $y;
        };

        $this->equals(
            TrySafe::success(5),
            TrySafe::lift($plus)($xSuccess, $ySuccess)
        );
        $this->equals(
            $failure,
            TrySafe::lift($plus)($xSuccess, $failure)
        );
    }

    public function testMap(): void
    {
        $mapper = function (string $s): string {
            return "Hello {$s}";
        };
        $mapperThatThrows = function () {
            throw new Exception();
        };

        $this->equals(
            TrySafe::success("Hello world"),
            TrySafe::success("world")->map($mapper)
        );

        self::assertTrue(TrySafe::success("Hello world")->map($mapperThatThrows)->isFailure());
        self::assertTrue($this->createFailure()->map($mapper)->isFailure());
        self::assertTrue($this->createFailure()->map($mapperThatThrows)->isFailure());
    }

    public function testFlatMap(): void
    {
        /** @phpstan-var callable(string): TrySafe<string> */
        $mapperToSuccess = function (string $s): TrySafe {
            return TrySafe::success("Hello {$s}");
        };
        /** @phpstan-var callable(string): TrySafe<string> */
        $mapperToFailure = function (string $s): TrySafe {
            return $this->createFailure();
        };
        /** @phpstan-var callable(string): TrySafe<string> */
        $mapperThatThrows = function () {
            throw new Exception();
        };

        $this->equals(
            TrySafe::success("Hello world"),
            TrySafe::success("world")->flatMap($mapperToSuccess)
        );

        self::assertTrue($this->createFailure()->flatMap($mapperToSuccess)->isFailure());
        self::assertTrue(TrySafe::success("world")->flatMap($mapperToFailure)->isFailure());
        self::assertTrue($this->createFailure()->flatMap($mapperToFailure)->isFailure());
        self::assertTrue(TrySafe::success("world")->flatMap($mapperThatThrows)->isFailure());
    }


    public function testRecover(): void
    {
        $failure = new Exception();

        $recoveredFailure = TrySafe::failure($failure)
            ->recover(function (Throwable $failure): int {
                return 666;
            });
        self::assertTrue($recoveredFailure->isSuccess());

        $exceptionThatRecoveryThrows = new Exception();
        $recoveryEndedWithFailure = TrySafe::failure($failure)
            ->recover(function (Throwable $failure) use ($exceptionThatRecoveryThrows) {
                throw $exceptionThatRecoveryThrows;
            });

        self::assertTrue($recoveryEndedWithFailure->isFailure());
        self::assertSame($exceptionThatRecoveryThrows, $recoveryEndedWithFailure->getFailureUnsafe());
    }

    public function testToOption(): void
    {
        $value = 666;
        self::assertEquals($value, TrySafe::success($value)->toOption()->getUnsafe());
        self::assertFalse($this->createFailure()->toOption()->isDefined());
    }

    public function testResolveSuccess(): void
    {
        $handleSuccessSpy = createInvokableSpy();
        $handleFailureSpy = createInvokableSpy();

        TrySafe::success(666)
            ->resolve($handleFailureSpy, $handleSuccessSpy);

        self::assertCount(1, $handleSuccessSpy->getCalls());
        self::assertCount(0, $handleFailureSpy->getCalls());
        self::assertEquals([[666]], $handleSuccessSpy->getCalls());
    }

    public function testResolveFailure(): void
    {
        $handleSuccessSpy = createInvokableSpy();
        $handleFailureSpy = createInvokableSpy();

        $exception = new Exception();
        TrySafe::failure($exception)
            ->resolve($handleFailureSpy, $handleSuccessSpy);

        self::assertCount(0, $handleSuccessSpy->getCalls());
        self::assertCount(1, $handleFailureSpy->getCalls());
        self::assertSame([[$exception]], $handleFailureSpy->getCalls());
    }


    public function testIterator(): void
    {
        $val = "Hello world";
        $success = TrySafe::success($val);
        $failure = $this->createFailure();

        self::assertEquals([$val], iterator_to_array($success->getIterator(), false));
        self::assertEquals([], iterator_to_array($failure->getIterator(), false));
    }

    public function testReduce(): void
    {
        $reducer = function (int $reduction, int $val): int {
            return $reduction + $val;
        };
        $initialReduction = 4;

        self::assertEquals(
            670,
            TrySafe::success(666)->reduce($reducer, $initialReduction)
        );
        self::assertEquals(
            $initialReduction,
            $this->createFailure()->reduce($reducer, $initialReduction)
        );
    }

    public function testLaws(): void
    {
        $assertEquals = function ($a, $b): void {
            $this->equals($a, $b);
        };
        $tryEquals = function (TrySafe $a, TrySafe $b): bool {
            return $a->equals($b);
        };
        $pure = function ($value): TrySafe {
            return TrySafe::of($value);
        };

        $successOne = TrySafe::success(1);
        $successTwo = TrySafe::success(2);
        $successThree = TrySafe::success(3);
        $failure = $this->createFailure();

        $plus2 = function (int $x): int {
            return $x + 2;
        };
        $multiple2 = function (int $x): int {
            return $x * 2;
        };
        $throws = function () {
            throw $this->createHashableException();
        };

        testEqualsReflexivity($assertEquals, $tryEquals, $successOne);
        testEqualsReflexivity($assertEquals, $tryEquals, $failure);

        testEqualsSymmetry($assertEquals, $tryEquals, $successOne, $successOne);
        testEqualsSymmetry($assertEquals, $tryEquals, $successOne, $successTwo);
        testEqualsSymmetry($assertEquals, $tryEquals, $successOne, $failure);
        testEqualsSymmetry($assertEquals, $tryEquals, $failure, $failure);

        testEqualsTransitivity($assertEquals, $tryEquals, $successOne, $successOne, $successOne);
        testEqualsTransitivity($assertEquals, $tryEquals, $successOne, $successTwo, $successThree);
        testEqualsTransitivity($assertEquals, $tryEquals, $successOne, $failure, $successThree);

        testFunctorIdentity($assertEquals, $successOne);
        testFunctorIdentity($assertEquals, $failure);

        testFunctorComposition($assertEquals, $successOne, $plus2, $multiple2);
        testFunctorComposition($assertEquals, $failure, $plus2, $multiple2);
        testFunctorComposition($assertEquals, $successOne, $plus2, $throws);
        testFunctorComposition($assertEquals, $successOne, $throws, $multiple2);
        testFunctorComposition($assertEquals, $failure, $plus2, $throws);
        testFunctorComposition($assertEquals, $failure, $throws, $multiple2);
        testFunctorComposition($assertEquals, $successOne, $throws, $throws);
        testFunctorComposition($assertEquals, $failure, $throws, $throws);

        testApplicativeIdentity($assertEquals, $pure, $successOne);
        testApplicativeIdentity($assertEquals, $pure, $failure);

        testApplicativeHomomorphism($assertEquals, $pure, 666, $multiple2);
        testApplicativeHomomorphism($assertEquals, $pure, 666, $multiple2);

        testApplicativeComposition($assertEquals, $pure, $successOne, $pure($plus2), $pure($multiple2));
        testApplicativeComposition($assertEquals, $pure, $failure, $pure($plus2), $pure($multiple2));
        testApplicativeComposition($assertEquals, $pure, $successOne, $failure, $pure($multiple2));
        testApplicativeComposition($assertEquals, $pure, $failure, $pure($plus2), $failure);

        testApplicativeInterchange($assertEquals, $pure, 666, $pure($plus2));
        testApplicativeInterchange($assertEquals, $pure, 666, $pure($throws));
        testApplicativeInterchange($assertEquals, $pure, 666, $failure);
    }

    /**
     * @phpstan-template T
     * @param T $a
     * @param T $b
     *
     * @return void
     */
    private function equals($a, $b): void
    {
        if ($a instanceof TrySafe && $b instanceof TrySafe) {
            self::assertTrue($a->equals($b));
        } else {
            self::assertEquals($a, $b);
        }
    }

    /**
     * @return TrySafe<mixed>
     */
    private function createFailure(): TrySafe
    {
        return TrySafe::failure($this->createHashableException());
    }

    private function createHashableException(): Exception
    {
        return new class extends Exception implements IHashable {
            public function hashCode()
            {
                return __CLASS__;
            }
        };
    }
}
