<?php

declare(strict_types=1);

namespace Bonami\Collection;

/**
 * @template L
 * @template R
 */
trait Applicative2
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
     * @param self<L, CurriedFunction<A, B>> $closure
     * @param self<L, A> $argument
     *
     * @return self<L, B>
     */
    public static function ap(self $closure, self $argument): self
    {
        return self::product($closure, $argument)->map(static fn (array $pair) => $pair[0]($pair[1]));
    }

    /**
     * Takes two arguments wrapped into type and creates product of those arguments wrapped into type
     *
     * @template A
     * @template B
     *
     * @param self<L, A> $a
     * @param self<L, B> $b
     *
     * @return self<L, array{A,B}>
     */
    abstract public static function product(self $a, self $b): self;

    /**
     * Maps over values wrapped in context of type class.
     *
     * @template A
     *
     * @param callable(R): A $mapper
     *
     * @return self<L, A>
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
        return static fn(self ...$arguments): self => self::sequence($arguments)
            ->map(static fn($args) => $callable(...$args));
    }

    /**
     * Upgrades callable with 1 argument to accept and return `self` as arguments.
     *
     * @template I1
     * @template O
     *
     * @param callable(I1): O $callable
     *
     * @return callable(self<L, I1>): self<L, O>
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
     * @return callable(self<L, I1>, self<L, I2>): self<L, O>
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
     * @return callable(self<L, I1>, self<L, I2>, self<L, I3>): self<L, O>
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
     * @return callable(self<L, I1>, self<L, I2>, self<L, I3>, self<L, I4>): self<L, O>
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
     * @return callable(self<L, I1>, self<L, I2>, self<L, I3>, self<L, I4>, self<L, I5>): self<L, O>
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
     * @return callable(self<L, I1>, self<L, I2>, self<L, I3>, self<L, I4>, self<L, I5>, self<L, I6>): self<L, O>
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
     * @return callable(self<L, I1>, self<L, I2>, self<L, I3>, self<L, I4>, self<L, I5>, self<L, I6>, self<L, I7>): self<L, O>
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
     * @return callable(self<L, I1>, self<L, I2>, self<L, I3>, self<L, I4>, self<L, I5>, self<L, I6>, self<L, I7>, self<L, I8>): self<L, O>
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
     * @return callable(self<L, I1>, self<L, I2>, self<L, I3>, self<L, I4>, self<L, I5>, self<L, I6>, self<L, I7>, self<L, I8>, self<L, I9>): self<L, O>
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
     * @return callable(self<L, I1>, self<L, I2>, self<L, I3>, self<L, I4>, self<L, I5>, self<L, I6>, self<L, I7>, self<L, I8>, self<L, I9>, self<L, I10>): self<L, O>
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
     * @return callable(self<L, I1>, self<L, I2>, self<L, I3>, self<L, I4>, self<L, I5>, self<L, I6>, self<L, I7>, self<L, I8>, self<L, I9>, self<L, I10>, self<L, I11>): self<L, O>
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
     * @return callable(self<L, I1>, self<L, I2>, self<L, I3>, self<L, I4>, self<L, I5>, self<L, I6>, self<L, I7>, self<L, I8>, self<L, I9>, self<L, I10>, self<L, I11>, self<L, I12>): self<L, O>
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
     * @return callable(self<L, I1>, self<L, I2>, self<L, I3>, self<L, I4>, self<L, I5>, self<L, I6>, self<L, I7>, self<L, I8>, self<L, I9>, self<L, I10>, self<L, I11>, self<L, I12>, self<L, I13>): self<L, O>
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
     * @return callable(self<L, I1>, self<L, I2>, self<L, I3>, self<L, I4>, self<L, I5>, self<L, I6>, self<L, I7>, self<L, I8>, self<L, I9>, self<L, I10>, self<L, I11>, self<L, I12>, self<L, I13>, self<L, I14>): self<L, O>
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
     * @return callable(self<L, I1>, self<L, I2>, self<L, I3>, self<L, I4>, self<L, I5>, self<L, I6>, self<L, I7>, self<L, I8>, self<L, I9>, self<L, I10>, self<L, I11>, self<L, I12>, self<L, I13>, self<L, I14>, self<L, I15>): self<L, O>
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
     * @return callable(self<L, I1>, self<L, I2>, self<L, I3>, self<L, I4>, self<L, I5>, self<L, I6>, self<L, I7>, self<L, I8>, self<L, I9>, self<L, I10>, self<L, I11>, self<L, I12>, self<L, I13>, self<L, I14>, self<L, I15>, self<L, I16>): self<L, O>
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
     * @return callable(self<L, I1>, self<L, I2>, self<L, I3>, self<L, I4>, self<L, I5>, self<L, I6>, self<L, I7>, self<L, I8>, self<L, I9>, self<L, I10>, self<L, I11>, self<L, I12>, self<L, I13>, self<L, I14>, self<L, I15>, self<L, I16>, self<L, I17>): self<L, O>
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
     * @return callable(self<L, I1>, self<L, I2>, self<L, I3>, self<L, I4>, self<L, I5>, self<L, I6>, self<L, I7>, self<L, I8>, self<L, I9>, self<L, I10>, self<L, I11>, self<L, I12>, self<L, I13>, self<L, I14>, self<L, I15>, self<L, I16>, self<L, I17>, self<L, I18>): self<L, O>
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
     * @return callable(self<L, I1>, self<L, I2>, self<L, I3>, self<L, I4>, self<L, I5>, self<L, I6>, self<L, I7>, self<L, I8>, self<L, I9>, self<L, I10>, self<L, I11>, self<L, I12>, self<L, I13>, self<L, I14>, self<L, I15>, self<L, I16>, self<L, I17>, self<L, I18>, self<L, I19>): self<L, O>
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
     * @return callable(self<L, I1>, self<L, I2>, self<L, I3>, self<L, I4>, self<L, I5>, self<L, I6>, self<L, I7>, self<L, I8>, self<L, I9>, self<L, I10>, self<L, I11>, self<L, I12>, self<L, I13>, self<L, I14>, self<L, I15>, self<L, I16>, self<L, I17>, self<L, I18>, self<L, I19>, self<L, I20>): self<L, O>
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
     * @return callable(self<L, I1>, self<L, I2>, self<L, I3>, self<L, I4>, self<L, I5>, self<L, I6>, self<L, I7>, self<L, I8>, self<L, I9>, self<L, I10>, self<L, I11>, self<L, I12>, self<L, I13>, self<L, I14>, self<L, I15>, self<L, I16>, self<L, I17>, self<L, I18>, self<L, I19>, self<L, I20>, self<L, I21>): self<L, O>
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
     * @return callable(self<L, I1>, self<L, I2>, self<L, I3>, self<L, I4>, self<L, I5>, self<L, I6>, self<L, I7>, self<L, I8>, self<L, I9>, self<L, I10>, self<L, I11>, self<L, I12>, self<L, I13>, self<L, I14>, self<L, I15>, self<L, I16>, self<L, I17>, self<L, I18>, self<L, I19>, self<L, I20>, self<L, I21>, self<L, I22>): self<L, O>
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
     * @return callable(self<L, I1>, self<L, I2>, self<L, I3>, self<L, I4>, self<L, I5>, self<L, I6>, self<L, I7>, self<L, I8>, self<L, I9>, self<L, I10>, self<L, I11>, self<L, I12>, self<L, I13>, self<L, I14>, self<L, I15>, self<L, I16>, self<L, I17>, self<L, I18>, self<L, I19>, self<L, I20>, self<L, I21>, self<L, I22>, self<L, I23>): self<L, O>
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
     * @return callable(self<L, I1>, self<L, I2>, self<L, I3>, self<L, I4>, self<L, I5>, self<L, I6>, self<L, I7>, self<L, I8>, self<L, I9>, self<L, I10>, self<L, I11>, self<L, I12>, self<L, I13>, self<L, I14>, self<L, I15>, self<L, I16>, self<L, I17>, self<L, I18>, self<L, I19>, self<L, I20>, self<L, I21>, self<L, I22>, self<L, I23>, self<L, I24>): self<L, O>
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
     * @return callable(self<L, I1>, self<L, I2>, self<L, I3>, self<L, I4>, self<L, I5>, self<L, I6>, self<L, I7>, self<L, I8>, self<L, I9>, self<L, I10>, self<L, I11>, self<L, I12>, self<L, I13>, self<L, I14>, self<L, I15>, self<L, I16>, self<L, I17>, self<L, I18>, self<L, I19>, self<L, I20>, self<L, I21>, self<L, I22>, self<L, I23>, self<L, I24>, self<L, I25>): self<L, O>
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
     * @return callable(self<L, I1>, self<L, I2>, self<L, I3>, self<L, I4>, self<L, I5>, self<L, I6>, self<L, I7>, self<L, I8>, self<L, I9>, self<L, I10>, self<L, I11>, self<L, I12>, self<L, I13>, self<L, I14>, self<L, I15>, self<L, I16>, self<L, I17>, self<L, I18>, self<L, I19>, self<L, I20>, self<L, I21>, self<L, I22>, self<L, I23>, self<L, I24>, self<L, I25>, self<L, I26>): self<L, O>
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
     * @return callable(self<L, I1>, self<L, I2>, self<L, I3>, self<L, I4>, self<L, I5>, self<L, I6>, self<L, I7>, self<L, I8>, self<L, I9>, self<L, I10>, self<L, I11>, self<L, I12>, self<L, I13>, self<L, I14>, self<L, I15>, self<L, I16>, self<L, I17>, self<L, I18>, self<L, I19>, self<L, I20>, self<L, I21>, self<L, I22>, self<L, I23>, self<L, I24>, self<L, I25>, self<L, I26>, self<L, I27>): self<L, O>
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
     * @return callable(self<L, I1>, self<L, I2>, self<L, I3>, self<L, I4>, self<L, I5>, self<L, I6>, self<L, I7>, self<L, I8>, self<L, I9>, self<L, I10>, self<L, I11>, self<L, I12>, self<L, I13>, self<L, I14>, self<L, I15>, self<L, I16>, self<L, I17>, self<L, I18>, self<L, I19>, self<L, I20>, self<L, I21>, self<L, I22>, self<L, I23>, self<L, I24>, self<L, I25>, self<L, I26>, self<L, I27>, self<L, I28>): self<L, O>
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
     * @return callable(self<L, I1>, self<L, I2>, self<L, I3>, self<L, I4>, self<L, I5>, self<L, I6>, self<L, I7>, self<L, I8>, self<L, I9>, self<L, I10>, self<L, I11>, self<L, I12>, self<L, I13>, self<L, I14>, self<L, I15>, self<L, I16>, self<L, I17>, self<L, I18>, self<L, I19>, self<L, I20>, self<L, I21>, self<L, I22>, self<L, I23>, self<L, I24>, self<L, I25>, self<L, I26>, self<L, I27>, self<L, I28>, self<L, I29>): self<L, O>
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
     * @return callable(self<L, I1>, self<L, I2>, self<L, I3>, self<L, I4>, self<L, I5>, self<L, I6>, self<L, I7>, self<L, I8>, self<L, I9>, self<L, I10>, self<L, I11>, self<L, I12>, self<L, I13>, self<L, I14>, self<L, I15>, self<L, I16>, self<L, I17>, self<L, I18>, self<L, I19>, self<L, I20>, self<L, I21>, self<L, I22>, self<L, I23>, self<L, I24>, self<L, I25>, self<L, I26>, self<L, I27>, self<L, I28>, self<L, I29>, self<L, I30>): self<L, O>
     */
    final public static function lift30(callable $callable): callable
    {
        return self::lift($callable);
    }

    /**
     * Takes any `iterable<self<L, R>>` and sequence it into `self<L, ArrayList<A>>`.
     * If any `self` is "empty", the result is "empty" as well.
     *
     * @template A
     *
     * @param iterable<self<L, A>> $iterable
     *
     * @return self<L, ArrayList<A>>
     */
    final public static function sequence(iterable $iterable): self
    {
        return LazyList::fromIterable($iterable)
            ->reduce(
                static fn(self $list, $x): self => self::product($list, $x)->map(
                    static fn (array $pair) => $pair[0]->add($pair[1]),
                ),
                self::pure(ArrayList::fromEmpty()),
            );
    }

    /**
     * Takes any `iterable<A>`, for each item `A` transforms to applicative with $mapperToApplicative
     * `A => self<L, B>` and cumulates it in `self<L, ArrayList<B>>`.
     *
     * @see sequence - behaves same as traverse, execept it is called with identity
     *
     * @template A
     * @template B
     *
     * @param iterable<A> $iterable
     * @param callable(A): self<L, B> $mapperToApplicative
     *
     * @return self<L, ArrayList<B>>
     */
    final public static function traverse(iterable $iterable, callable $mapperToApplicative): self
    {
        return LazyList::fromIterable($iterable)
            ->reduce(
                static fn(self $list, $x): self => self::product($list, $mapperToApplicative($x))->map(
                    static fn (array $pair) => $pair[0]->add($pair[1]),
                ),
                self::pure(ArrayList::fromEmpty()),
            );
    }
}
