<?php

declare(strict_types=1);

namespace Bonami\Collection;

use Bonami\Collection\Hash\IHashable;
use Exception;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Throwable;

class TrySafeTest extends TestCase
{

    public function testCreate(): void
    {
        $trySafeFromScalar = TrySafe::of(666);
        self::assertTrue($trySafeFromScalar->isSuccess());

        $trySafeFromException = TrySafe::of(new Exception('Exception can act as success value as well'));
        self::assertTrue($trySafeFromException->isSuccess());
    }

    public function testCreateFromCallable(): void
    {
        $returnValueCallable = static function () {
            return 666;
        };
        $throwCallable = static function () {
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

        $plus = static function (int $x, int $y): int {
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
        $mapper = static function (string $s): string {
            return sprintf('Hello %s', $s);
        };
        $mapperThatThrows = static function () {
            throw new Exception();
        };

        $this->equals(
            TrySafe::success('Hello world'),
            TrySafe::success('world')->map($mapper)
        );

        self::assertTrue(TrySafe::success('Hello world')->map($mapperThatThrows)->isFailure());
        self::assertTrue($this->createFailure()->map($mapper)->isFailure());
        self::assertTrue($this->createFailure()->map($mapperThatThrows)->isFailure());
    }

    public function testEach(): void
    {
        $success = TrySafe::success(1);
        $failure = TrySafe::failure(new Exception());
        $accumulated = 0;

        $accumulate = static function (int $i) use (&$accumulated): void {
            $accumulated += $i;
        };

        $failure->each($accumulate);
        self::assertEquals(0, $accumulated);

        $success->each($accumulate);
        self::assertEquals(1, $accumulated);
    }

    public function testFlatMap(): void
    {
        /** @phpstan-var callable(string): TrySafe<string> */
        $mapperToSuccess = static function (string $s): TrySafe {
            return TrySafe::success(sprintf('Hello %s', $s));
        };
        /** @phpstan-var callable(string): TrySafe<string> */
        $mapperToFailure = function (string $s): TrySafe {
            return $this->createFailure();
        };
        /** @phpstan-var callable(string): TrySafe<string> */
        $mapperThatThrows = static function () {
            throw new Exception();
        };

        $this->equals(
            TrySafe::success('Hello world'),
            TrySafe::success('world')->flatMap($mapperToSuccess)
        );

        self::assertTrue($this->createFailure()->flatMap($mapperToSuccess)->isFailure());
        self::assertTrue(TrySafe::success('world')->flatMap($mapperToFailure)->isFailure());
        self::assertTrue($this->createFailure()->flatMap($mapperToFailure)->isFailure());
        self::assertTrue(TrySafe::success('world')->flatMap($mapperThatThrows)->isFailure());
    }

    public function testRecover(): void
    {
        $failure = new Exception();

        $recoveredFailure = TrySafe::failure($failure)
            ->recover(static function (Throwable $failure): int {
                return 666;
            });
        self::assertTrue($recoveredFailure->isSuccess());

        $exceptionThatRecoveryThrows = new Exception();
        $recoveryEndedWithFailure = TrySafe::failure($failure)
            ->recover(static function (Throwable $failure) use ($exceptionThatRecoveryThrows) {
                throw $exceptionThatRecoveryThrows;
            });

        self::assertTrue($recoveryEndedWithFailure->isFailure());
        self::assertSame($exceptionThatRecoveryThrows, $recoveryEndedWithFailure->getFailureUnsafe());
    }

    public function testRecoverIf(): void
    {
        $failure = new Exception();

        self::assertTrue(TrySafe::failure($failure)
            ->recoverIf(
                tautology(),
                static function (Throwable $failure): int {
                    return 666;
                }
            )
            ->isSuccess());

        self::assertTrue(TrySafe::failure($failure)
            ->recoverIf(
                falsy(),
                static function (Throwable $failure): int {
                    return 666;
                }
            )
            ->isFailure());

        $exceptionThatRecoveryThrows = new Exception();
        $recoveryEndedWithFailure = TrySafe::failure($failure)
            ->recoverIf(tautology(), static function (Throwable $failure) use ($exceptionThatRecoveryThrows) {
                throw $exceptionThatRecoveryThrows;
            });

        self::assertTrue($recoveryEndedWithFailure->isFailure());
        self::assertSame($exceptionThatRecoveryThrows, $recoveryEndedWithFailure->getFailureUnsafe());
    }

    public function testRecoverWith(): void
    {
        $success = TrySafe::success(42);
        $failure = TrySafe::failure(new Exception());

        $recover = static function (Throwable $ex): TrySafe {
            return TrySafe::success(666);
        };
        $exceptionThatRecoveryThrows = new Exception();
        $throw = static function (Throwable $ex) use ($exceptionThatRecoveryThrows) {
            throw $exceptionThatRecoveryThrows;
        };
        $exceptionThatRecoveryWraps = new Exception();
        $wrap = static function (Throwable $failure) use ($exceptionThatRecoveryWraps) {
            return TrySafe::failure($exceptionThatRecoveryWraps);
        };

        self::assertSame(42, $success->recoverWith($recover)->getUnsafe());
        self::assertTrue($success->recoverWith($recover)->isSuccess());

        self::assertSame(42, $success->recoverWith($throw)->getUnsafe());
        self::assertTrue($success->recoverWith($throw)->isSuccess());

        self::assertSame(42, $success->recoverWith($wrap)->getUnsafe());
        self::assertTrue($success->recoverWith($wrap)->isSuccess());

        self::assertTrue($failure->recoverWith($recover)->isSuccess());
        self::assertSame(666, $failure->recoverWith($recover)->getUnsafe());

        self::assertTrue($failure->recoverWith($throw)->isFailure());
        self::assertSame($exceptionThatRecoveryThrows, $failure->recoverWith($throw)->getFailureUnsafe());

        self::assertTrue($failure->recoverWith($wrap)->isFailure());
        self::assertSame($exceptionThatRecoveryWraps, $failure->recoverWith($wrap)->getFailureUnsafe());
    }

    public function testRecoverWithIf(): void
    {
        $originalException = new Exception();
        $success = TrySafe::success(42);
        $failure = TrySafe::failure($originalException);

        $recover = static function (Throwable $ex): TrySafe {
            return TrySafe::success(666);
        };
        $matchRuntimeException = static function (Throwable $throwable): bool {
            return $throwable instanceof RuntimeException;
        };
        $matchAll = static function (Throwable $throwable): bool {
            return true;
        };
        $exceptionThatRecoveryThrows = new Exception();
        $throw = static function (Throwable $ex) use ($exceptionThatRecoveryThrows) {
            throw $exceptionThatRecoveryThrows;
        };
        $exceptionThatRecoveryWraps = new Exception();
        $wrap = static function (Throwable $failure) use ($exceptionThatRecoveryWraps) {
            return TrySafe::failure($exceptionThatRecoveryWraps);
        };

        self::assertSame(42, $success->recoverWithIf($matchRuntimeException, $recover)->getUnsafe());
        self::assertTrue($success->recoverWithIf($matchRuntimeException, $recover)->isSuccess());

        self::assertSame(42, $success->recoverWithIf($matchRuntimeException, $throw)->getUnsafe());
        self::assertTrue($success->recoverWithIf($matchRuntimeException, $throw)->isSuccess());

        self::assertSame(42, $success->recoverWithIf($matchRuntimeException, $wrap)->getUnsafe());
        self::assertTrue($success->recoverWithIf($matchRuntimeException, $wrap)->isSuccess());

        self::assertTrue($failure->recoverWithIf($matchRuntimeException, $recover)->isFailure());
        self::assertTrue($failure->recoverWithIf($matchAll, $recover)->isSuccess());
        self::assertSame(666, $failure->recoverWithIf($matchAll, $recover)->getUnsafe());

        self::assertTrue($failure->recoverWithIf($matchAll, $throw)->isFailure());
        self::assertSame($exceptionThatRecoveryThrows, $failure->recoverWithIf($matchAll, $throw)->getFailureUnsafe());
        self::assertSame(
            $originalException,
            $failure->recoverWithIf($matchRuntimeException, $throw)->getFailureUnsafe()
        );

        self::assertTrue($failure->recoverWithIf($matchRuntimeException, $wrap)->isFailure());
        self::assertTrue($failure->recoverWithIf($matchAll, $wrap)->isFailure());
        self::assertSame($exceptionThatRecoveryWraps, $failure->recoverWithIf($matchAll, $wrap)->getFailureUnsafe());
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
        $val = 'Hello world';
        $success = TrySafe::success($val);
        $failure = $this->createFailure();

        self::assertEquals([$val], iterator_to_array($success->getIterator(), false));
        self::assertEquals([], iterator_to_array($failure->getIterator(), false));
    }

    public function testReduce(): void
    {
        $reducer = static function (int $reduction, int $val): int {
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
        $tryEquals = static function (TrySafe $a, TrySafe $b): bool {
            return $a->equals($b);
        };
        $pure = static function ($value): TrySafe {
            return TrySafe::of($value);
        };

        $successOne = TrySafe::success(1);
        $successTwo = TrySafe::success(2);
        $successThree = TrySafe::success(3);
        $failure = $this->createFailure();

        $plus2 = static function (int $x): int {
            return $x + 2;
        };
        $multiple2 = static function (int $x): int {
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
     * @template T
     *
     * @phpstan-param T $a
     * @phpstan-param T $b
     *
     * @phpstan-return void
     */
    private function equals($a, $b): void
    {
        if ($a instanceof TrySafe && $b instanceof TrySafe) {
            self::assertTrue($a->equals($b));
        } else {
            self::assertEquals($a, $b);
        }
    }

    /** @phpstan-return TrySafe<mixed> */
    private function createFailure(): TrySafe
    {
        return TrySafe::failure($this->createHashableException());
    }

    private function createHashableException(): Throwable
    {
        return new class extends Exception implements IHashable {
            public function hashCode()
            {
                return self::class;
            }
        };
    }
}
