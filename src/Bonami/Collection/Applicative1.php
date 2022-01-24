<?php

declare(strict_types=1);

namespace Bonami\Collection;

/** @template T */
trait Applicative1
{
    /**
     * Wraps impure item into pure context of type class.
     *
     * @template A
     *
     * @param A $value
     *
     * @return static<A>
     */
    abstract public static function pure($value);

    /**
     * Applies argument to callable in context of type class.
     *
     * @template A
     * @template B
     *
     * @param self<callable(A): B> $closure
     * @param self<A> $argument
     *
     * @return self<B>
     */
    abstract public static function ap(self $closure, self $argument): self;

    /**
     * Maps over values wrapped in context of type class.
     *
     * @template A
     *
     * @param callable(T): A $mapper
     *
     * @return self<A>
     */
    abstract public function map(callable $mapper): self;

    /**
     * Upgrades callable to accept and return `self` as arguments.
     *
     * @param callable $callable
     *
     * @return callable
     */
    final public static function lift(callable $callable): callable
    {
        return static function (self ...$arguments) use ($callable): self {
            return self::sequence($arguments)->map(static function ($args) use ($callable) {
                return $callable(...$args);
            });
        };
    }

    /**
     * Upgrades callable with 1 argument to accept and return `self` as arguments.
     *
     * @template I1
     * @template O
     *
     * @param callable(I1): O $callable
     *
     * @return callable(self<I1>): self<O>
     */
    final public static function lift1(callable $callable): callable
    {
        return self::lift($callable);
    }

    /**
     * Upgrades callable with 2 arguments to accept and return `self` as arguments.
     *
     * @template I1
     * @template I2
     * @template O
     *
     * @param callable(I1, I2): O $callable
     *
     * @return callable(self<I1>, self<I2>): self<O>
     */
    final public static function lift2(callable $callable): callable
    {
        return self::lift($callable);
    }

    /**
     * Upgrades callable with 3 arguments to accept and return `self` as arguments.
     *
     * @template I1
     * @template I2
     * @template I3
     * @template O
     *
     * @param callable(I1, I2, I3): O $callable
     *
     * @return callable(self<I1>, self<I2>, self<I3>): self<O>
     */
    final public static function lift3(callable $callable): callable
    {
        return self::lift($callable);
    }

    /**
     * Upgrades callable with 4 arguments to accept and return `self` as arguments.
     *
     * @template I1
     * @template I2
     * @template I3
     * @template I4
     * @template O
     *
     * @param callable(I1, I2, I3, I4): O $callable
     *
     * @return callable(self<I1>, self<I2>, self<I3>, self<I4>): self<O>
     */
    final public static function lift4(callable $callable): callable
    {
        return self::lift($callable);
    }

    /**
     * Upgrades callable with 5 arguments to accept and return `self` as arguments.
     *
     * @template I1
     * @template I2
     * @template I3
     * @template I4
     * @template I5
     * @template O
     *
     * @param callable(I1, I2, I3, I4, I5): O $callable
     *
     * @return callable(self<I1>, self<I2>, self<I3>, self<I4>, self<I5>): self<O>
     */
    final public static function lift5(callable $callable): callable
    {
        return self::lift($callable);
    }

    /**
     * Upgrades callable with 6 arguments to accept and return `self` as arguments.
     *
     * @template I1
     * @template I2
     * @template I3
     * @template I4
     * @template I5
     * @template I6
     * @template O
     *
     * @param callable(I1, I2, I3, I4, I5, I6): O $callable
     *
     * @return callable(self<I1>, self<I2>, self<I3>, self<I4>, self<I5>, self<I6>): self<O>
     */
    final public static function lift6(callable $callable): callable
    {
        return self::lift($callable);
    }

    /**
     * Upgrades callable with 7 arguments to accept and return `self` as arguments.
     *
     * @template I1
     * @template I2
     * @template I3
     * @template I4
     * @template I5
     * @template I6
     * @template I7
     * @template O
     *
     * @param callable(I1, I2, I3, I4, I5, I6, I7): O $callable
     *
     * @return callable(self<I1>, self<I2>, self<I3>, self<I4>, self<I5>, self<I6>, self<I7>): self<O>
     */
    final public static function lift7(callable $callable): callable
    {
        return self::lift($callable);
    }

    /**
     * Upgrades callable with 8 arguments to accept and return `self` as arguments.
     *
     * @template I1
     * @template I2
     * @template I3
     * @template I4
     * @template I5
     * @template I6
     * @template I7
     * @template I8
     * @template O
     *
     * @param callable(I1, I2, I3, I4, I5, I6, I7, I8): O $callable
     *
     * @return callable(self<I1>, self<I2>, self<I3>, self<I4>, self<I5>, self<I6>, self<I7>, self<I8>): self<O>
     */
    final public static function lift8(callable $callable): callable
    {
        return self::lift($callable);
    }

    /**
     * Upgrades callable with 9 arguments to accept and return `self` as arguments.
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
     * @template O
     *
     * @param callable(I1, I2, I3, I4, I5, I6, I7, I8, I9): O $callable
     *
     * @return callable(self<I1>, self<I2>, self<I3>, self<I4>, self<I5>, self<I6>, self<I7>, self<I8>, self<I9>): self<O>
     */
    final public static function lift9(callable $callable): callable
    {
        return self::lift($callable);
    }

    /**
     * Upgrades callable with 10 arguments to accept and return `self` as arguments.
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
     * @template O
     *
     * @param callable(I1, I2, I3, I4, I5, I6, I7, I8, I9, I10): O $callable
     *
     * @return callable(self<I1>, self<I2>, self<I3>, self<I4>, self<I5>, self<I6>, self<I7>, self<I8>, self<I9>, self<I10>): self<O>
     */
    final public static function lift10(callable $callable): callable
    {
        return self::lift($callable);
    }

    /**
     * Upgrades callable with 11 arguments to accept and return `self` as arguments.
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
     * @template O
     *
     * @param callable(I1, I2, I3, I4, I5, I6, I7, I8, I9, I10, I11): O $callable
     *
     * @return callable(self<I1>, self<I2>, self<I3>, self<I4>, self<I5>, self<I6>, self<I7>, self<I8>, self<I9>, self<I10>, self<I11>): self<O>
     */
    final public static function lift11(callable $callable): callable
    {
        return self::lift($callable);
    }

    /**
     * Upgrades callable with 12 arguments to accept and return `self` as arguments.
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
     * @template O
     *
     * @param callable(I1, I2, I3, I4, I5, I6, I7, I8, I9, I10, I11, I12): O $callable
     *
     * @return callable(self<I1>, self<I2>, self<I3>, self<I4>, self<I5>, self<I6>, self<I7>, self<I8>, self<I9>, self<I10>, self<I11>, self<I12>): self<O>
     */
    final public static function lift12(callable $callable): callable
    {
        return self::lift($callable);
    }

    /**
     * Upgrades callable with 13 arguments to accept and return `self` as arguments.
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
     * @template O
     *
     * @param callable(I1, I2, I3, I4, I5, I6, I7, I8, I9, I10, I11, I12, I13): O $callable
     *
     * @return callable(self<I1>, self<I2>, self<I3>, self<I4>, self<I5>, self<I6>, self<I7>, self<I8>, self<I9>, self<I10>, self<I11>, self<I12>, self<I13>): self<O>
     */
    final public static function lift13(callable $callable): callable
    {
        return self::lift($callable);
    }

    /**
     * Upgrades callable with 14 arguments to accept and return `self` as arguments.
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
     * @template O
     *
     * @param callable(I1, I2, I3, I4, I5, I6, I7, I8, I9, I10, I11, I12, I13, I14): O $callable
     *
     * @return callable(self<I1>, self<I2>, self<I3>, self<I4>, self<I5>, self<I6>, self<I7>, self<I8>, self<I9>, self<I10>, self<I11>, self<I12>, self<I13>, self<I14>): self<O>
     */
    final public static function lift14(callable $callable): callable
    {
        return self::lift($callable);
    }

    /**
     * Upgrades callable with 15 arguments to accept and return `self` as arguments.
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
     * @template O
     *
     * @param callable(I1, I2, I3, I4, I5, I6, I7, I8, I9, I10, I11, I12, I13, I14, I15): O $callable
     *
     * @return callable(self<I1>, self<I2>, self<I3>, self<I4>, self<I5>, self<I6>, self<I7>, self<I8>, self<I9>, self<I10>, self<I11>, self<I12>, self<I13>, self<I14>, self<I15>): self<O>
     */
    final public static function lift15(callable $callable): callable
    {
        return self::lift($callable);
    }

    /**
     * Upgrades callable with 16 arguments to accept and return `self` as arguments.
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
     * @template O
     *
     * @param callable(I1, I2, I3, I4, I5, I6, I7, I8, I9, I10, I11, I12, I13, I14, I15, I16): O $callable
     *
     * @return callable(self<I1>, self<I2>, self<I3>, self<I4>, self<I5>, self<I6>, self<I7>, self<I8>, self<I9>, self<I10>, self<I11>, self<I12>, self<I13>, self<I14>, self<I15>, self<I16>): self<O>
     */
    final public static function lift16(callable $callable): callable
    {
        return self::lift($callable);
    }

    /**
     * Upgrades callable with 17 arguments to accept and return `self` as arguments.
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
     * @template O
     *
     * @param callable(I1, I2, I3, I4, I5, I6, I7, I8, I9, I10, I11, I12, I13, I14, I15, I16, I17): O $callable
     *
     * @return callable(self<I1>, self<I2>, self<I3>, self<I4>, self<I5>, self<I6>, self<I7>, self<I8>, self<I9>, self<I10>, self<I11>, self<I12>, self<I13>, self<I14>, self<I15>, self<I16>, self<I17>): self<O>
     */
    final public static function lift17(callable $callable): callable
    {
        return self::lift($callable);
    }

    /**
     * Upgrades callable with 18 arguments to accept and return `self` as arguments.
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
     * @template O
     *
     * @param callable(I1, I2, I3, I4, I5, I6, I7, I8, I9, I10, I11, I12, I13, I14, I15, I16, I17, I18): O $callable
     *
     * @return callable(self<I1>, self<I2>, self<I3>, self<I4>, self<I5>, self<I6>, self<I7>, self<I8>, self<I9>, self<I10>, self<I11>, self<I12>, self<I13>, self<I14>, self<I15>, self<I16>, self<I17>, self<I18>): self<O>
     */
    final public static function lift18(callable $callable): callable
    {
        return self::lift($callable);
    }

    /**
     * Upgrades callable with 19 arguments to accept and return `self` as arguments.
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
     * @template O
     *
     * @param callable(I1, I2, I3, I4, I5, I6, I7, I8, I9, I10, I11, I12, I13, I14, I15, I16, I17, I18, I19): O $callable
     *
     * @return callable(self<I1>, self<I2>, self<I3>, self<I4>, self<I5>, self<I6>, self<I7>, self<I8>, self<I9>, self<I10>, self<I11>, self<I12>, self<I13>, self<I14>, self<I15>, self<I16>, self<I17>, self<I18>, self<I19>): self<O>
     */
    final public static function lift19(callable $callable): callable
    {
        return self::lift($callable);
    }

    /**
     * Upgrades callable with 20 arguments to accept and return `self` as arguments.
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
     * @template O
     *
     * @param callable(I1, I2, I3, I4, I5, I6, I7, I8, I9, I10, I11, I12, I13, I14, I15, I16, I17, I18, I19, I20): O $callable
     *
     * @return callable(self<I1>, self<I2>, self<I3>, self<I4>, self<I5>, self<I6>, self<I7>, self<I8>, self<I9>, self<I10>, self<I11>, self<I12>, self<I13>, self<I14>, self<I15>, self<I16>, self<I17>, self<I18>, self<I19>, self<I20>): self<O>
     */
    final public static function lift20(callable $callable): callable
    {
        return self::lift($callable);
    }

    /**
     * Upgrades callable with 21 arguments to accept and return `self` as arguments.
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
     * @template O
     *
     * @param callable(I1, I2, I3, I4, I5, I6, I7, I8, I9, I10, I11, I12, I13, I14, I15, I16, I17, I18, I19, I20, I21): O $callable
     *
     * @return callable(self<I1>, self<I2>, self<I3>, self<I4>, self<I5>, self<I6>, self<I7>, self<I8>, self<I9>, self<I10>, self<I11>, self<I12>, self<I13>, self<I14>, self<I15>, self<I16>, self<I17>, self<I18>, self<I19>, self<I20>, self<I21>): self<O>
     */
    final public static function lift21(callable $callable): callable
    {
        return self::lift($callable);
    }

    /**
     * Upgrades callable with 22 arguments to accept and return `self` as arguments.
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
     * @template O
     *
     * @param callable(I1, I2, I3, I4, I5, I6, I7, I8, I9, I10, I11, I12, I13, I14, I15, I16, I17, I18, I19, I20, I21, I22): O $callable
     *
     * @return callable(self<I1>, self<I2>, self<I3>, self<I4>, self<I5>, self<I6>, self<I7>, self<I8>, self<I9>, self<I10>, self<I11>, self<I12>, self<I13>, self<I14>, self<I15>, self<I16>, self<I17>, self<I18>, self<I19>, self<I20>, self<I21>, self<I22>): self<O>
     */
    final public static function lift22(callable $callable): callable
    {
        return self::lift($callable);
    }

    /**
     * Upgrades callable with 23 arguments to accept and return `self` as arguments.
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
     * @template O
     *
     * @param callable(I1, I2, I3, I4, I5, I6, I7, I8, I9, I10, I11, I12, I13, I14, I15, I16, I17, I18, I19, I20, I21, I22, I23): O $callable
     *
     * @return callable(self<I1>, self<I2>, self<I3>, self<I4>, self<I5>, self<I6>, self<I7>, self<I8>, self<I9>, self<I10>, self<I11>, self<I12>, self<I13>, self<I14>, self<I15>, self<I16>, self<I17>, self<I18>, self<I19>, self<I20>, self<I21>, self<I22>, self<I23>): self<O>
     */
    final public static function lift23(callable $callable): callable
    {
        return self::lift($callable);
    }

    /**
     * Upgrades callable with 24 arguments to accept and return `self` as arguments.
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
     * @template O
     *
     * @param callable(I1, I2, I3, I4, I5, I6, I7, I8, I9, I10, I11, I12, I13, I14, I15, I16, I17, I18, I19, I20, I21, I22, I23, I24): O $callable
     *
     * @return callable(self<I1>, self<I2>, self<I3>, self<I4>, self<I5>, self<I6>, self<I7>, self<I8>, self<I9>, self<I10>, self<I11>, self<I12>, self<I13>, self<I14>, self<I15>, self<I16>, self<I17>, self<I18>, self<I19>, self<I20>, self<I21>, self<I22>, self<I23>, self<I24>): self<O>
     */
    final public static function lift24(callable $callable): callable
    {
        return self::lift($callable);
    }

    /**
     * Upgrades callable with 25 arguments to accept and return `self` as arguments.
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
     * @template O
     *
     * @param callable(I1, I2, I3, I4, I5, I6, I7, I8, I9, I10, I11, I12, I13, I14, I15, I16, I17, I18, I19, I20, I21, I22, I23, I24, I25): O $callable
     *
     * @return callable(self<I1>, self<I2>, self<I3>, self<I4>, self<I5>, self<I6>, self<I7>, self<I8>, self<I9>, self<I10>, self<I11>, self<I12>, self<I13>, self<I14>, self<I15>, self<I16>, self<I17>, self<I18>, self<I19>, self<I20>, self<I21>, self<I22>, self<I23>, self<I24>, self<I25>): self<O>
     */
    final public static function lift25(callable $callable): callable
    {
        return self::lift($callable);
    }

    /**
     * Upgrades callable with 26 arguments to accept and return `self` as arguments.
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
     * @template O
     *
     * @param callable(I1, I2, I3, I4, I5, I6, I7, I8, I9, I10, I11, I12, I13, I14, I15, I16, I17, I18, I19, I20, I21, I22, I23, I24, I25, I26): O $callable
     *
     * @return callable(self<I1>, self<I2>, self<I3>, self<I4>, self<I5>, self<I6>, self<I7>, self<I8>, self<I9>, self<I10>, self<I11>, self<I12>, self<I13>, self<I14>, self<I15>, self<I16>, self<I17>, self<I18>, self<I19>, self<I20>, self<I21>, self<I22>, self<I23>, self<I24>, self<I25>, self<I26>): self<O>
     */
    final public static function lift26(callable $callable): callable
    {
        return self::lift($callable);
    }

    /**
     * Upgrades callable with 27 arguments to accept and return `self` as arguments.
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
     * @template O
     *
     * @param callable(I1, I2, I3, I4, I5, I6, I7, I8, I9, I10, I11, I12, I13, I14, I15, I16, I17, I18, I19, I20, I21, I22, I23, I24, I25, I26, I27): O $callable
     *
     * @return callable(self<I1>, self<I2>, self<I3>, self<I4>, self<I5>, self<I6>, self<I7>, self<I8>, self<I9>, self<I10>, self<I11>, self<I12>, self<I13>, self<I14>, self<I15>, self<I16>, self<I17>, self<I18>, self<I19>, self<I20>, self<I21>, self<I22>, self<I23>, self<I24>, self<I25>, self<I26>, self<I27>): self<O>
     */
    final public static function lift27(callable $callable): callable
    {
        return self::lift($callable);
    }

    /**
     * Upgrades callable with 28 arguments to accept and return `self` as arguments.
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
     * @template O
     *
     * @param callable(I1, I2, I3, I4, I5, I6, I7, I8, I9, I10, I11, I12, I13, I14, I15, I16, I17, I18, I19, I20, I21, I22, I23, I24, I25, I26, I27, I28): O $callable
     *
     * @return callable(self<I1>, self<I2>, self<I3>, self<I4>, self<I5>, self<I6>, self<I7>, self<I8>, self<I9>, self<I10>, self<I11>, self<I12>, self<I13>, self<I14>, self<I15>, self<I16>, self<I17>, self<I18>, self<I19>, self<I20>, self<I21>, self<I22>, self<I23>, self<I24>, self<I25>, self<I26>, self<I27>, self<I28>): self<O>
     */
    final public static function lift28(callable $callable): callable
    {
        return self::lift($callable);
    }

    /**
     * Upgrades callable with 29 arguments to accept and return `self` as arguments.
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
     * @template O
     *
     * @param callable(I1, I2, I3, I4, I5, I6, I7, I8, I9, I10, I11, I12, I13, I14, I15, I16, I17, I18, I19, I20, I21, I22, I23, I24, I25, I26, I27, I28, I29): O $callable
     *
     * @return callable(self<I1>, self<I2>, self<I3>, self<I4>, self<I5>, self<I6>, self<I7>, self<I8>, self<I9>, self<I10>, self<I11>, self<I12>, self<I13>, self<I14>, self<I15>, self<I16>, self<I17>, self<I18>, self<I19>, self<I20>, self<I21>, self<I22>, self<I23>, self<I24>, self<I25>, self<I26>, self<I27>, self<I28>, self<I29>): self<O>
     */
    final public static function lift29(callable $callable): callable
    {
        return self::lift($callable);
    }

    /**
     * Upgrades callable with 30 arguments to accept and return `self` as arguments.
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
     * @template O
     *
     * @param callable(I1, I2, I3, I4, I5, I6, I7, I8, I9, I10, I11, I12, I13, I14, I15, I16, I17, I18, I19, I20, I21, I22, I23, I24, I25, I26, I27, I28, I29, I30): O $callable
     *
     * @return callable(self<I1>, self<I2>, self<I3>, self<I4>, self<I5>, self<I6>, self<I7>, self<I8>, self<I9>, self<I10>, self<I11>, self<I12>, self<I13>, self<I14>, self<I15>, self<I16>, self<I17>, self<I18>, self<I19>, self<I20>, self<I21>, self<I22>, self<I23>, self<I24>, self<I25>, self<I26>, self<I27>, self<I28>, self<I29>, self<I30>): self<O>
     */
    final public static function lift30(callable $callable): callable
    {
        return self::lift($callable);
    }

    /**
     * Takes any `iterable<self<A>>` and sequence it into `self<ArrayList<A>>`. If any `self` is "empty", the result is
     * "empty" as well.
     *
     * @template A
     *
     * @param iterable<self<A>> $iterable
     *
     * @return self<ArrayList<A>>
     */
    final public static function sequence(iterable $iterable): self
    {
        // @phpstan-ignore-next-line
        return self::traverse($iterable, identity());
    }

    /**
     * Takes any `iterable<A>`, for each item `A` transforms to applicative with $mapperToApplicative
     * `A => self<B>` and cumulates it in `self<ArrayList<B>>`.
     *
     * @see sequence - behaves same as traverse, execept it is called with identity
     *
     * @template A
     * @template B
     *
     * @param iterable<A> $iterable
     * @param callable(A): self<B> $mapperToApplicative
     *
     * @return self<ArrayList<B>>
     */
    final public static function traverse(iterable $iterable, callable $mapperToApplicative): self
    {
        // @phpstan-ignore-next-line
        return LazyList::fromIterable($iterable)
            ->reduce(
                static function (self $reducedApplicative, $impureItem) use ($mapperToApplicative): self {
                    $applicative = $mapperToApplicative($impureItem);
                    assert($applicative instanceof self);
                    return self::ap(
                        $reducedApplicative
                            ->map(static function (ArrayList $resultIterable): callable {
                                return CurriedFunction::of(static function ($item) use ($resultIterable): ArrayList {
                                    return $resultIterable->concat(ArrayList::of($item));
                                });
                            }),
                        $applicative
                    );
                },
                self::pure(ArrayList::fromEmpty())
            );
    }
}
