<?php

declare(strict_types=1);

namespace Bonami\Collection;

use Bonami\Collection\Exception\InvalidStateException;
use Closure;
use ReflectionFunction;

/**
 * Represents single argument function
 *
 * @template I
 * @template O
 */
final class CurriedFunction
{
    /** @var callable */
    protected $callable;

    /** @var array<mixed> */
    protected $applied;

    /** @var int|null */
    protected $numberOfArgs;

    /**
     * @param callable $callable      - closure to wrap and convert into curried closure
     * @param int $expectedNumberOfArgs  - if ommited, number of arguments is detected
     *                                          with slight performance impact
     * @param array<mixed> $applied   - applied arguments tracked for delayed full aplication of final argument
     */
    private function __construct(callable $callable, int $expectedNumberOfArgs, array $applied = [])
    {
        $numberOfArgs = (new ReflectionFunction(Closure::fromCallable($callable)))->getNumberOfParameters();
        if ($numberOfArgs !== $expectedNumberOfArgs) {
            throw new InvalidStateException(sprintf(
                'Passed function must accept exactly %s arguments',
                $expectedNumberOfArgs
            ));
        }

        $this->callable = $callable;
        $this->numberOfArgs = $numberOfArgs;
        $this->applied = $applied;
    }

    /**
     * Wraps single argument callable into CurriedFunction. It is semantically the same, but it allows calling methods
     * on  it, like `map` for function composition.
     *
     * @template A
     * @template Z
     *
     * @param callable(A): Z $callable
     *
     * @return self<A, Z>
     */
    public static function of(callable $callable): self
    {
        if ($callable instanceof self) {
            return $callable;
        }
        return new self($callable, 1);
    }

    /**
     * Converts function with 1 arguments to curried version, which accepts one argument
     * at time and returns another function that accepts another argument up to last one,
     * then it returns the actual result.
     *
     * That way we can do partial function application, which allows some cool FP usages.
     *
     * @template I1
     * @template Z
     *
     * @param callable(I1): Z $callable
     *
     * @return self<I1, Z>
     */
    public static function curry1(callable $callable): self
    {
        return $callable instanceof self ? $callable : new self($callable, 1);
    }

    /**
     * Converts function with 2 arguments to curried version, which accepts one argument
     * at time and returns another function that accepts another argument up to last one,
     * then it returns the actual result.
     *
     * That way we can do partial function application, which allows some cool FP usages.
     *
     * @template I1
     * @template I2
     * @template Z
     *
     * @param callable(I1, I2): Z $callable
     *
     * @return self<I1, self<I2, Z>>
     */
    public static function curry2(callable $callable): self
    {
        return $callable instanceof self ? $callable : new self($callable, 2);
    }

    /**
     * Converts function with 3 arguments to curried version, which accepts one argument
     * at time and returns another function that accepts another argument up to last one,
     * then it returns the actual result.
     *
     * That way we can do partial function application, which allows some cool FP usages.
     *
     * @template I1
     * @template I2
     * @template I3
     * @template Z
     *
     * @param callable(I1, I2, I3): Z $callable
     *
     * @return self<I1, self<I2, self<I3, Z>>>
     */
    public static function curry3(callable $callable): self
    {
        return $callable instanceof self ? $callable : new self($callable, 3);
    }

    /**
     * Converts function with 4 arguments to curried version, which accepts one argument
     * at time and returns another function that accepts another argument up to last one,
     * then it returns the actual result.
     *
     * That way we can do partial function application, which allows some cool FP usages.
     *
     * @template I1
     * @template I2
     * @template I3
     * @template I4
     * @template Z
     *
     * @param callable(I1, I2, I3, I4): Z $callable
     *
     * @return self<I1, self<I2, self<I3, self<I4, Z>>>>
     */
    public static function curry4(callable $callable): self
    {
        return $callable instanceof self ? $callable : new self($callable, 4);
    }

    /**
     * Converts function with 5 arguments to curried version, which accepts one argument
     * at time and returns another function that accepts another argument up to last one,
     * then it returns the actual result.
     *
     * That way we can do partial function application, which allows some cool FP usages.
     *
     * @template I1
     * @template I2
     * @template I3
     * @template I4
     * @template I5
     * @template Z
     *
     * @param callable(I1, I2, I3, I4, I5): Z $callable
     *
     * @return self<I1, self<I2, self<I3, self<I4, self<I5, Z>>>>>
     */
    public static function curry5(callable $callable): self
    {
        return $callable instanceof self ? $callable : new self($callable, 5);
    }

    /**
     * Converts function with 6 arguments to curried version, which accepts one argument
     * at time and returns another function that accepts another argument up to last one,
     * then it returns the actual result.
     *
     * That way we can do partial function application, which allows some cool FP usages.
     *
     * @template I1
     * @template I2
     * @template I3
     * @template I4
     * @template I5
     * @template I6
     * @template Z
     *
     * @param callable(I1, I2, I3, I4, I5, I6): Z $callable
     *
     * @return self<I1, self<I2, self<I3, self<I4, self<I5, self<I6, Z>>>>>>
     */
    public static function curry6(callable $callable): self
    {
        return $callable instanceof self ? $callable : new self($callable, 6);
    }

    /**
     * Converts function with 7 arguments to curried version, which accepts one argument
     * at time and returns another function that accepts another argument up to last one,
     * then it returns the actual result.
     *
     * That way we can do partial function application, which allows some cool FP usages.
     *
     * @template I1
     * @template I2
     * @template I3
     * @template I4
     * @template I5
     * @template I6
     * @template I7
     * @template Z
     *
     * @param callable(I1, I2, I3, I4, I5, I6, I7): Z $callable
     *
     * @return self<I1, self<I2, self<I3, self<I4, self<I5, self<I6, self<I7, Z>>>>>>>
     */
    public static function curry7(callable $callable): self
    {
        return $callable instanceof self ? $callable : new self($callable, 7);
    }

    /**
     * Converts function with 8 arguments to curried version, which accepts one argument
     * at time and returns another function that accepts another argument up to last one,
     * then it returns the actual result.
     *
     * That way we can do partial function application, which allows some cool FP usages.
     *
     * @template I1
     * @template I2
     * @template I3
     * @template I4
     * @template I5
     * @template I6
     * @template I7
     * @template I8
     * @template Z
     *
     * @param callable(I1, I2, I3, I4, I5, I6, I7, I8): Z $callable
     *
     * @return self<I1, self<I2, self<I3, self<I4, self<I5, self<I6, self<I7, self<I8, Z>>>>>>>>
     */
    public static function curry8(callable $callable): self
    {
        return $callable instanceof self ? $callable : new self($callable, 8);
    }

    /**
     * Converts function with 9 arguments to curried version, which accepts one argument
     * at time and returns another function that accepts another argument up to last one,
     * then it returns the actual result.
     *
     * That way we can do partial function application, which allows some cool FP usages.
     *
     * @template I1
     * @template I2
     * @template I3
     * @template I4
     * @template I5
     * @template I6
     * @template I7
     * @template I8
     * @template I9
     * @template Z
     *
     * @param callable(I1, I2, I3, I4, I5, I6, I7, I8, I9): Z $callable
     *
     * @return self<I1, self<I2, self<I3, self<I4, self<I5, self<I6, self<I7, self<I8, self<I9, Z>>>>>>>>>
     */
    public static function curry9(callable $callable): self
    {
        return $callable instanceof self ? $callable : new self($callable, 9);
    }

    /**
     * Converts function with 10 arguments to curried version, which accepts one argument
     * at time and returns another function that accepts another argument up to last one,
     * then it returns the actual result.
     *
     * That way we can do partial function application, which allows some cool FP usages.
     *
     * @template I1
     * @template I2
     * @template I3
     * @template I4
     * @template I5
     * @template I6
     * @template I7
     * @template I8
     * @template I9
     * @template I10
     * @template Z
     *
     * @param callable(I1, I2, I3, I4, I5, I6, I7, I8, I9, I10): Z $callable
     *
     * @return self<I1, self<I2, self<I3, self<I4, self<I5, self<I6, self<I7, self<I8, self<I9, self<I10, Z>>>>>>>>>>
     */
    public static function curry10(callable $callable): self
    {
        return $callable instanceof self ? $callable : new self($callable, 10);
    }

    /**
     * Converts function with 11 arguments to curried version, which accepts one argument
     * at time and returns another function that accepts another argument up to last one,
     * then it returns the actual result.
     *
     * That way we can do partial function application, which allows some cool FP usages.
     *
     * @template I1
     * @template I2
     * @template I3
     * @template I4
     * @template I5
     * @template I6
     * @template I7
     * @template I8
     * @template I9
     * @template I10
     * @template I11
     * @template Z
     *
     * @param callable(I1, I2, I3, I4, I5, I6, I7, I8, I9, I10, I11): Z $callable
     *
     * @return self<I1, self<I2, self<I3, self<I4, self<I5, self<I6, self<I7, self<I8, self<I9, self<I10, self<I11, Z>>>>>>>>>>>
     */
    public static function curry11(callable $callable): self
    {
        return $callable instanceof self ? $callable : new self($callable, 11);
    }

    /**
     * Converts function with 12 arguments to curried version, which accepts one argument
     * at time and returns another function that accepts another argument up to last one,
     * then it returns the actual result.
     *
     * That way we can do partial function application, which allows some cool FP usages.
     *
     * @template I1
     * @template I2
     * @template I3
     * @template I4
     * @template I5
     * @template I6
     * @template I7
     * @template I8
     * @template I9
     * @template I10
     * @template I11
     * @template I12
     * @template Z
     *
     * @param callable(I1, I2, I3, I4, I5, I6, I7, I8, I9, I10, I11, I12): Z $callable
     *
     * @return self<I1, self<I2, self<I3, self<I4, self<I5, self<I6, self<I7, self<I8, self<I9, self<I10, self<I11, self<I12, Z>>>>>>>>>>>>
     */
    public static function curry12(callable $callable): self
    {
        return $callable instanceof self ? $callable : new self($callable, 12);
    }

    /**
     * Converts function with 13 arguments to curried version, which accepts one argument
     * at time and returns another function that accepts another argument up to last one,
     * then it returns the actual result.
     *
     * That way we can do partial function application, which allows some cool FP usages.
     *
     * @template I1
     * @template I2
     * @template I3
     * @template I4
     * @template I5
     * @template I6
     * @template I7
     * @template I8
     * @template I9
     * @template I10
     * @template I11
     * @template I12
     * @template I13
     * @template Z
     *
     * @param callable(I1, I2, I3, I4, I5, I6, I7, I8, I9, I10, I11, I12, I13): Z $callable
     *
     * @return self<I1, self<I2, self<I3, self<I4, self<I5, self<I6, self<I7, self<I8, self<I9, self<I10, self<I11, self<I12, self<I13, Z>>>>>>>>>>>>>
     */
    public static function curry13(callable $callable): self
    {
        return $callable instanceof self ? $callable : new self($callable, 13);
    }

    /**
     * Converts function with 14 arguments to curried version, which accepts one argument
     * at time and returns another function that accepts another argument up to last one,
     * then it returns the actual result.
     *
     * That way we can do partial function application, which allows some cool FP usages.
     *
     * @template I1
     * @template I2
     * @template I3
     * @template I4
     * @template I5
     * @template I6
     * @template I7
     * @template I8
     * @template I9
     * @template I10
     * @template I11
     * @template I12
     * @template I13
     * @template I14
     * @template Z
     *
     * @param callable(I1, I2, I3, I4, I5, I6, I7, I8, I9, I10, I11, I12, I13, I14): Z $callable
     *
     * @return self<I1, self<I2, self<I3, self<I4, self<I5, self<I6, self<I7, self<I8, self<I9, self<I10, self<I11, self<I12, self<I13, self<I14, Z>>>>>>>>>>>>>>
     */
    public static function curry14(callable $callable): self
    {
        return $callable instanceof self ? $callable : new self($callable, 14);
    }

    /**
     * Converts function with 15 arguments to curried version, which accepts one argument
     * at time and returns another function that accepts another argument up to last one,
     * then it returns the actual result.
     *
     * That way we can do partial function application, which allows some cool FP usages.
     *
     * @template I1
     * @template I2
     * @template I3
     * @template I4
     * @template I5
     * @template I6
     * @template I7
     * @template I8
     * @template I9
     * @template I10
     * @template I11
     * @template I12
     * @template I13
     * @template I14
     * @template I15
     * @template Z
     *
     * @param callable(I1, I2, I3, I4, I5, I6, I7, I8, I9, I10, I11, I12, I13, I14, I15): Z $callable
     *
     * @return self<I1, self<I2, self<I3, self<I4, self<I5, self<I6, self<I7, self<I8, self<I9, self<I10, self<I11, self<I12, self<I13, self<I14, self<I15, Z>>>>>>>>>>>>>>>
     */
    public static function curry15(callable $callable): self
    {
        return $callable instanceof self ? $callable : new self($callable, 15);
    }

    /**
     * Converts function with 16 arguments to curried version, which accepts one argument
     * at time and returns another function that accepts another argument up to last one,
     * then it returns the actual result.
     *
     * That way we can do partial function application, which allows some cool FP usages.
     *
     * @template I1
     * @template I2
     * @template I3
     * @template I4
     * @template I5
     * @template I6
     * @template I7
     * @template I8
     * @template I9
     * @template I10
     * @template I11
     * @template I12
     * @template I13
     * @template I14
     * @template I15
     * @template I16
     * @template Z
     *
     * @param callable(I1, I2, I3, I4, I5, I6, I7, I8, I9, I10, I11, I12, I13, I14, I15, I16): Z $callable
     *
     * @return self<I1, self<I2, self<I3, self<I4, self<I5, self<I6, self<I7, self<I8, self<I9, self<I10, self<I11, self<I12, self<I13, self<I14, self<I15, self<I16, Z>>>>>>>>>>>>>>>>
     */
    public static function curry16(callable $callable): self
    {
        return $callable instanceof self ? $callable : new self($callable, 16);
    }

    /**
     * Converts function with 17 arguments to curried version, which accepts one argument
     * at time and returns another function that accepts another argument up to last one,
     * then it returns the actual result.
     *
     * That way we can do partial function application, which allows some cool FP usages.
     *
     * @template I1
     * @template I2
     * @template I3
     * @template I4
     * @template I5
     * @template I6
     * @template I7
     * @template I8
     * @template I9
     * @template I10
     * @template I11
     * @template I12
     * @template I13
     * @template I14
     * @template I15
     * @template I16
     * @template I17
     * @template Z
     *
     * @param callable(I1, I2, I3, I4, I5, I6, I7, I8, I9, I10, I11, I12, I13, I14, I15, I16, I17): Z $callable
     *
     * @return self<I1, self<I2, self<I3, self<I4, self<I5, self<I6, self<I7, self<I8, self<I9, self<I10, self<I11, self<I12, self<I13, self<I14, self<I15, self<I16, self<I17, Z>>>>>>>>>>>>>>>>>
     */
    public static function curry17(callable $callable): self
    {
        return $callable instanceof self ? $callable : new self($callable, 17);
    }

    /**
     * Converts function with 18 arguments to curried version, which accepts one argument
     * at time and returns another function that accepts another argument up to last one,
     * then it returns the actual result.
     *
     * That way we can do partial function application, which allows some cool FP usages.
     *
     * @template I1
     * @template I2
     * @template I3
     * @template I4
     * @template I5
     * @template I6
     * @template I7
     * @template I8
     * @template I9
     * @template I10
     * @template I11
     * @template I12
     * @template I13
     * @template I14
     * @template I15
     * @template I16
     * @template I17
     * @template I18
     * @template Z
     *
     * @param callable(I1, I2, I3, I4, I5, I6, I7, I8, I9, I10, I11, I12, I13, I14, I15, I16, I17, I18): Z $callable
     *
     * @return self<I1, self<I2, self<I3, self<I4, self<I5, self<I6, self<I7, self<I8, self<I9, self<I10, self<I11, self<I12, self<I13, self<I14, self<I15, self<I16, self<I17, self<I18, Z>>>>>>>>>>>>>>>>>>
     */
    public static function curry18(callable $callable): self
    {
        return $callable instanceof self ? $callable : new self($callable, 18);
    }

    /**
     * Converts function with 19 arguments to curried version, which accepts one argument
     * at time and returns another function that accepts another argument up to last one,
     * then it returns the actual result.
     *
     * That way we can do partial function application, which allows some cool FP usages.
     *
     * @template I1
     * @template I2
     * @template I3
     * @template I4
     * @template I5
     * @template I6
     * @template I7
     * @template I8
     * @template I9
     * @template I10
     * @template I11
     * @template I12
     * @template I13
     * @template I14
     * @template I15
     * @template I16
     * @template I17
     * @template I18
     * @template I19
     * @template Z
     *
     * @param callable(I1, I2, I3, I4, I5, I6, I7, I8, I9, I10, I11, I12, I13, I14, I15, I16, I17, I18, I19): Z $callable
     *
     * @return self<I1, self<I2, self<I3, self<I4, self<I5, self<I6, self<I7, self<I8, self<I9, self<I10, self<I11, self<I12, self<I13, self<I14, self<I15, self<I16, self<I17, self<I18, self<I19, Z>>>>>>>>>>>>>>>>>>>
     */
    public static function curry19(callable $callable): self
    {
        return $callable instanceof self ? $callable : new self($callable, 19);
    }

    /**
     * Converts function with 20 arguments to curried version, which accepts one argument
     * at time and returns another function that accepts another argument up to last one,
     * then it returns the actual result.
     *
     * That way we can do partial function application, which allows some cool FP usages.
     *
     * @template I1
     * @template I2
     * @template I3
     * @template I4
     * @template I5
     * @template I6
     * @template I7
     * @template I8
     * @template I9
     * @template I10
     * @template I11
     * @template I12
     * @template I13
     * @template I14
     * @template I15
     * @template I16
     * @template I17
     * @template I18
     * @template I19
     * @template I20
     * @template Z
     *
     * @param callable(I1, I2, I3, I4, I5, I6, I7, I8, I9, I10, I11, I12, I13, I14, I15, I16, I17, I18, I19, I20): Z $callable
     *
     * @return self<I1, self<I2, self<I3, self<I4, self<I5, self<I6, self<I7, self<I8, self<I9, self<I10, self<I11, self<I12, self<I13, self<I14, self<I15, self<I16, self<I17, self<I18, self<I19, self<I20, Z>>>>>>>>>>>>>>>>>>>>
     */
    public static function curry20(callable $callable): self
    {
        return $callable instanceof self ? $callable : new self($callable, 20);
    }

    /**
     * Converts function with 21 arguments to curried version, which accepts one argument
     * at time and returns another function that accepts another argument up to last one,
     * then it returns the actual result.
     *
     * That way we can do partial function application, which allows some cool FP usages.
     *
     * @template I1
     * @template I2
     * @template I3
     * @template I4
     * @template I5
     * @template I6
     * @template I7
     * @template I8
     * @template I9
     * @template I10
     * @template I11
     * @template I12
     * @template I13
     * @template I14
     * @template I15
     * @template I16
     * @template I17
     * @template I18
     * @template I19
     * @template I20
     * @template I21
     * @template Z
     *
     * @param callable(I1, I2, I3, I4, I5, I6, I7, I8, I9, I10, I11, I12, I13, I14, I15, I16, I17, I18, I19, I20, I21): Z $callable
     *
     * @return self<I1, self<I2, self<I3, self<I4, self<I5, self<I6, self<I7, self<I8, self<I9, self<I10, self<I11, self<I12, self<I13, self<I14, self<I15, self<I16, self<I17, self<I18, self<I19, self<I20, self<I21, Z>>>>>>>>>>>>>>>>>>>>>
     */
    public static function curry21(callable $callable): self
    {
        return $callable instanceof self ? $callable : new self($callable, 21);
    }

    /**
     * Converts function with 22 arguments to curried version, which accepts one argument
     * at time and returns another function that accepts another argument up to last one,
     * then it returns the actual result.
     *
     * That way we can do partial function application, which allows some cool FP usages.
     *
     * @template I1
     * @template I2
     * @template I3
     * @template I4
     * @template I5
     * @template I6
     * @template I7
     * @template I8
     * @template I9
     * @template I10
     * @template I11
     * @template I12
     * @template I13
     * @template I14
     * @template I15
     * @template I16
     * @template I17
     * @template I18
     * @template I19
     * @template I20
     * @template I21
     * @template I22
     * @template Z
     *
     * @param callable(I1, I2, I3, I4, I5, I6, I7, I8, I9, I10, I11, I12, I13, I14, I15, I16, I17, I18, I19, I20, I21, I22): Z $callable
     *
     * @return self<I1, self<I2, self<I3, self<I4, self<I5, self<I6, self<I7, self<I8, self<I9, self<I10, self<I11, self<I12, self<I13, self<I14, self<I15, self<I16, self<I17, self<I18, self<I19, self<I20, self<I21, self<I22, Z>>>>>>>>>>>>>>>>>>>>>>
     */
    public static function curry22(callable $callable): self
    {
        return $callable instanceof self ? $callable : new self($callable, 22);
    }

    /**
     * Converts function with 23 arguments to curried version, which accepts one argument
     * at time and returns another function that accepts another argument up to last one,
     * then it returns the actual result.
     *
     * That way we can do partial function application, which allows some cool FP usages.
     *
     * @template I1
     * @template I2
     * @template I3
     * @template I4
     * @template I5
     * @template I6
     * @template I7
     * @template I8
     * @template I9
     * @template I10
     * @template I11
     * @template I12
     * @template I13
     * @template I14
     * @template I15
     * @template I16
     * @template I17
     * @template I18
     * @template I19
     * @template I20
     * @template I21
     * @template I22
     * @template I23
     * @template Z
     *
     * @param callable(I1, I2, I3, I4, I5, I6, I7, I8, I9, I10, I11, I12, I13, I14, I15, I16, I17, I18, I19, I20, I21, I22, I23): Z $callable
     *
     * @return self<I1, self<I2, self<I3, self<I4, self<I5, self<I6, self<I7, self<I8, self<I9, self<I10, self<I11, self<I12, self<I13, self<I14, self<I15, self<I16, self<I17, self<I18, self<I19, self<I20, self<I21, self<I22, self<I23, Z>>>>>>>>>>>>>>>>>>>>>>>
     */
    public static function curry23(callable $callable): self
    {
        return $callable instanceof self ? $callable : new self($callable, 23);
    }

    /**
     * Converts function with 24 arguments to curried version, which accepts one argument
     * at time and returns another function that accepts another argument up to last one,
     * then it returns the actual result.
     *
     * That way we can do partial function application, which allows some cool FP usages.
     *
     * @template I1
     * @template I2
     * @template I3
     * @template I4
     * @template I5
     * @template I6
     * @template I7
     * @template I8
     * @template I9
     * @template I10
     * @template I11
     * @template I12
     * @template I13
     * @template I14
     * @template I15
     * @template I16
     * @template I17
     * @template I18
     * @template I19
     * @template I20
     * @template I21
     * @template I22
     * @template I23
     * @template I24
     * @template Z
     *
     * @param callable(I1, I2, I3, I4, I5, I6, I7, I8, I9, I10, I11, I12, I13, I14, I15, I16, I17, I18, I19, I20, I21, I22, I23, I24): Z $callable
     *
     * @return self<I1, self<I2, self<I3, self<I4, self<I5, self<I6, self<I7, self<I8, self<I9, self<I10, self<I11, self<I12, self<I13, self<I14, self<I15, self<I16, self<I17, self<I18, self<I19, self<I20, self<I21, self<I22, self<I23, self<I24, Z>>>>>>>>>>>>>>>>>>>>>>>>
     */
    public static function curry24(callable $callable): self
    {
        return $callable instanceof self ? $callable : new self($callable, 24);
    }

    /**
     * Converts function with 25 arguments to curried version, which accepts one argument
     * at time and returns another function that accepts another argument up to last one,
     * then it returns the actual result.
     *
     * That way we can do partial function application, which allows some cool FP usages.
     *
     * @template I1
     * @template I2
     * @template I3
     * @template I4
     * @template I5
     * @template I6
     * @template I7
     * @template I8
     * @template I9
     * @template I10
     * @template I11
     * @template I12
     * @template I13
     * @template I14
     * @template I15
     * @template I16
     * @template I17
     * @template I18
     * @template I19
     * @template I20
     * @template I21
     * @template I22
     * @template I23
     * @template I24
     * @template I25
     * @template Z
     *
     * @param callable(I1, I2, I3, I4, I5, I6, I7, I8, I9, I10, I11, I12, I13, I14, I15, I16, I17, I18, I19, I20, I21, I22, I23, I24, I25): Z $callable
     *
     * @return self<I1, self<I2, self<I3, self<I4, self<I5, self<I6, self<I7, self<I8, self<I9, self<I10, self<I11, self<I12, self<I13, self<I14, self<I15, self<I16, self<I17, self<I18, self<I19, self<I20, self<I21, self<I22, self<I23, self<I24, self<I25, Z>>>>>>>>>>>>>>>>>>>>>>>>>
     */
    public static function curry25(callable $callable): self
    {
        return $callable instanceof self ? $callable : new self($callable, 25);
    }

    /**
     * Converts function with 26 arguments to curried version, which accepts one argument
     * at time and returns another function that accepts another argument up to last one,
     * then it returns the actual result.
     *
     * That way we can do partial function application, which allows some cool FP usages.
     *
     * @template I1
     * @template I2
     * @template I3
     * @template I4
     * @template I5
     * @template I6
     * @template I7
     * @template I8
     * @template I9
     * @template I10
     * @template I11
     * @template I12
     * @template I13
     * @template I14
     * @template I15
     * @template I16
     * @template I17
     * @template I18
     * @template I19
     * @template I20
     * @template I21
     * @template I22
     * @template I23
     * @template I24
     * @template I25
     * @template I26
     * @template Z
     *
     * @param callable(I1, I2, I3, I4, I5, I6, I7, I8, I9, I10, I11, I12, I13, I14, I15, I16, I17, I18, I19, I20, I21, I22, I23, I24, I25, I26): Z $callable
     *
     * @return self<I1, self<I2, self<I3, self<I4, self<I5, self<I6, self<I7, self<I8, self<I9, self<I10, self<I11, self<I12, self<I13, self<I14, self<I15, self<I16, self<I17, self<I18, self<I19, self<I20, self<I21, self<I22, self<I23, self<I24, self<I25, self<I26, Z>>>>>>>>>>>>>>>>>>>>>>>>>>
     */
    public static function curry26(callable $callable): self
    {
        return $callable instanceof self ? $callable : new self($callable, 26);
    }

    /**
     * Converts function with 27 arguments to curried version, which accepts one argument
     * at time and returns another function that accepts another argument up to last one,
     * then it returns the actual result.
     *
     * That way we can do partial function application, which allows some cool FP usages.
     *
     * @template I1
     * @template I2
     * @template I3
     * @template I4
     * @template I5
     * @template I6
     * @template I7
     * @template I8
     * @template I9
     * @template I10
     * @template I11
     * @template I12
     * @template I13
     * @template I14
     * @template I15
     * @template I16
     * @template I17
     * @template I18
     * @template I19
     * @template I20
     * @template I21
     * @template I22
     * @template I23
     * @template I24
     * @template I25
     * @template I26
     * @template I27
     * @template Z
     *
     * @param callable(I1, I2, I3, I4, I5, I6, I7, I8, I9, I10, I11, I12, I13, I14, I15, I16, I17, I18, I19, I20, I21, I22, I23, I24, I25, I26, I27): Z $callable
     *
     * @return self<I1, self<I2, self<I3, self<I4, self<I5, self<I6, self<I7, self<I8, self<I9, self<I10, self<I11, self<I12, self<I13, self<I14, self<I15, self<I16, self<I17, self<I18, self<I19, self<I20, self<I21, self<I22, self<I23, self<I24, self<I25, self<I26, self<I27, Z>>>>>>>>>>>>>>>>>>>>>>>>>>>
     */
    public static function curry27(callable $callable): self
    {
        return $callable instanceof self ? $callable : new self($callable, 27);
    }

    /**
     * Converts function with 28 arguments to curried version, which accepts one argument
     * at time and returns another function that accepts another argument up to last one,
     * then it returns the actual result.
     *
     * That way we can do partial function application, which allows some cool FP usages.
     *
     * @template I1
     * @template I2
     * @template I3
     * @template I4
     * @template I5
     * @template I6
     * @template I7
     * @template I8
     * @template I9
     * @template I10
     * @template I11
     * @template I12
     * @template I13
     * @template I14
     * @template I15
     * @template I16
     * @template I17
     * @template I18
     * @template I19
     * @template I20
     * @template I21
     * @template I22
     * @template I23
     * @template I24
     * @template I25
     * @template I26
     * @template I27
     * @template I28
     * @template Z
     *
     * @param callable(I1, I2, I3, I4, I5, I6, I7, I8, I9, I10, I11, I12, I13, I14, I15, I16, I17, I18, I19, I20, I21, I22, I23, I24, I25, I26, I27, I28): Z $callable
     *
     * @return self<I1, self<I2, self<I3, self<I4, self<I5, self<I6, self<I7, self<I8, self<I9, self<I10, self<I11, self<I12, self<I13, self<I14, self<I15, self<I16, self<I17, self<I18, self<I19, self<I20, self<I21, self<I22, self<I23, self<I24, self<I25, self<I26, self<I27, self<I28, Z>>>>>>>>>>>>>>>>>>>>>>>>>>>>
     */
    public static function curry28(callable $callable): self
    {
        return $callable instanceof self ? $callable : new self($callable, 28);
    }

    /**
     * Converts function with 29 arguments to curried version, which accepts one argument
     * at time and returns another function that accepts another argument up to last one,
     * then it returns the actual result.
     *
     * That way we can do partial function application, which allows some cool FP usages.
     *
     * @template I1
     * @template I2
     * @template I3
     * @template I4
     * @template I5
     * @template I6
     * @template I7
     * @template I8
     * @template I9
     * @template I10
     * @template I11
     * @template I12
     * @template I13
     * @template I14
     * @template I15
     * @template I16
     * @template I17
     * @template I18
     * @template I19
     * @template I20
     * @template I21
     * @template I22
     * @template I23
     * @template I24
     * @template I25
     * @template I26
     * @template I27
     * @template I28
     * @template I29
     * @template Z
     *
     * @param callable(I1, I2, I3, I4, I5, I6, I7, I8, I9, I10, I11, I12, I13, I14, I15, I16, I17, I18, I19, I20, I21, I22, I23, I24, I25, I26, I27, I28, I29): Z $callable
     *
     * @return self<I1, self<I2, self<I3, self<I4, self<I5, self<I6, self<I7, self<I8, self<I9, self<I10, self<I11, self<I12, self<I13, self<I14, self<I15, self<I16, self<I17, self<I18, self<I19, self<I20, self<I21, self<I22, self<I23, self<I24, self<I25, self<I26, self<I27, self<I28, self<I29, Z>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
     */
    public static function curry29(callable $callable): self
    {
        return $callable instanceof self ? $callable : new self($callable, 29);
    }

    /**
     * Converts function with 30 arguments to curried version, which accepts one argument
     * at time and returns another function that accepts another argument up to last one,
     * then it returns the actual result.
     *
     * That way we can do partial function application, which allows some cool FP usages.
     *
     * @template I1
     * @template I2
     * @template I3
     * @template I4
     * @template I5
     * @template I6
     * @template I7
     * @template I8
     * @template I9
     * @template I10
     * @template I11
     * @template I12
     * @template I13
     * @template I14
     * @template I15
     * @template I16
     * @template I17
     * @template I18
     * @template I19
     * @template I20
     * @template I21
     * @template I22
     * @template I23
     * @template I24
     * @template I25
     * @template I26
     * @template I27
     * @template I28
     * @template I29
     * @template I30
     * @template Z
     *
     * @param callable(I1, I2, I3, I4, I5, I6, I7, I8, I9, I10, I11, I12, I13, I14, I15, I16, I17, I18, I19, I20, I21, I22, I23, I24, I25, I26, I27, I28, I29, I30): Z $callable
     *
     * @return self<I1, self<I2, self<I3, self<I4, self<I5, self<I6, self<I7, self<I8, self<I9, self<I10, self<I11, self<I12, self<I13, self<I14, self<I15, self<I16, self<I17, self<I18, self<I19, self<I20, self<I21, self<I22, self<I23, self<I24, self<I25, self<I26, self<I27, self<I28, self<I29, self<I30, Z>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
     */
    public static function curry30(callable $callable): self
    {
        return $callable instanceof self ? $callable : new self($callable, 30);
    }

    /**
     * Mapping over function is equivalent of composing functions
     *
     * `$f->map($g)` is equivalent of `g ∘ f` or `g(f(x))` in mathematics notation.
     *
     * @template A
     *
     * @param CurriedFunction<O, A> $then
     *
     * @return CurriedFunction<I, A>
     */
    public function map(CurriedFunction $then): CurriedFunction
    {
        return self::of(function ($arg) use ($then) {
            return $then($this($arg));
        });
    }

    /**
     * @param I $arg
     *
     * @return O
     */
    public function __invoke($arg)
    {
        if ($this->numberOfArgs === null) {
            $this->numberOfArgs = (new ReflectionFunction(
                Closure::fromCallable($this->callable)
            ))->getNumberOfParameters();
        }
        $newApplied = $this->applied;
        $newApplied[] = $arg;
        $numberOfArgsLeft = $this->numberOfArgs - count($newApplied);

        return $numberOfArgsLeft > 0
            ? new self($this->callable, $this->numberOfArgs, $newApplied)
            : ($this->callable)(...$newApplied);
    }
}
