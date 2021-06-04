<?php

declare(strict_types=1);

namespace Bonami\Collection;

/**
 * @phpstan-param callable(mixed, mixed): void $assertEquals
 * @phpstan-param callable(mixed, mixed): bool $equals
 * @phpstan-param mixed $a
 *
 * @phpstan-return void
 */
function testEqualsReflexivity(callable $assertEquals, callable $equals, $a): void
{
    $assertEquals(true, $equals($a, $a));
}

/**
 * @template T
 *
 * @phpstan-param callable(bool, bool): void $assertEquals
 * @phpstan-param callable(mixed, mixed): bool $equals
 * @phpstan-param T $a
 * @phpstan-param T $b
 *
 * @phpstan-return void
 */
function testEqualsSymmetry(callable $assertEquals, callable $equals, $a, $b): void
{
    $assertEquals(
        $equals($a, $b),
        $equals($b, $a)
    );
}

/**
 * @template T
 *
 * @phpstan-param callable(bool, bool): void $assertEquals
 * @phpstan-param callable(mixed, mixed): bool $equals
 * @phpstan-param T $a
 * @phpstan-param T $b
 * @phpstan-param T $c
 *
 * @phpstan-return void
 */
function testEqualsTransitivity(callable $assertEquals, callable $equals, $a, $b, $c): void
{
    $assertEquals(
        $equals($a, $b) && $equals($b, $c),
        $equals($a, $c)
    );
}

/* @see https://wiki.haskell.org/Functor#Functor_Laws */

/**
 * @phpstan-param callable(mixed, mixed): void $assertEquals
 * @phpstan-param mixed $functor - this should implement some generic functor interface
 *
 * @phpstan-return void
 */
function testFunctorIdentity(callable $assertEquals, $functor): void
{
    $id = static function ($a) {
        return $a;
    };
    $assertEquals(
        $functor,
        $functor->map($id)
    );
}

/**
 * @phpstan-param callable(mixed, mixed): void $assertEquals
 * @phpstan-param mixed $functor - this should implement some generic functor interface
 * @phpstan-param callable(mixed): mixed $f
 * @phpstan-param callable(mixed): mixed $g
 *
 * @phpstan-return void
 */
function testFunctorComposition(callable $assertEquals, $functor, callable $f, callable $g): void
{
    $assertEquals(
        $functor->map($g)->map($f),
        $functor->map(compose($f, $g))
    );
}

/* @see https://en.wikibooks.org/wiki/Haskell/Applicative_functors#Applicative_functor_laws */

/**
 * @phpstan-param callable(mixed, mixed): void $assertEquals
 * @phpstan-param callable(mixed): mixed $pure
 * @phpstan-param mixed $applicative - this should implement some generic applicative interface
 *
 * @phpstan-return void
 */
function testApplicativeIdentity(callable $assertEquals, callable $pure, $applicative): void
{
    $assertEquals(
        $pure(identity())->ap($applicative),
        $applicative
    );
}

/**
 * @phpstan-param callable(mixed, mixed): void $assertEquals
 * @phpstan-param callable(mixed): mixed $pure
 * @phpstan-param mixed $value
 * @phpstan-param callable(mixed): mixed $f
 *
 * @phpstan-return void
 */
function testApplicativeHomomorphism(callable $assertEquals, callable $pure, $value, callable $f): void
{
    $assertEquals(
        $pure($f)->ap($pure($value)),
        $pure($f($value))
    );
}

/**
 * @phpstan-param callable(mixed, mixed): void $assertEquals
 * @phpstan-param callable(mixed): mixed $pure
 * @phpstan-param mixed $value
 * @phpstan-param mixed $applicativeF - this should implement some generic applicative interface
 *
 * @phpstan-return void
 */
function testApplicativeInterchange(callable $assertEquals, callable $pure, $value, $applicativeF): void
{
    $assertEquals(
        $applicativeF->ap($pure($value)),
        $pure(applicator($value))->ap($applicativeF)
    );
}

/**
 * @phpstan-param callable(mixed, mixed): void $assertEquals
 * @phpstan-param callable(mixed): mixed $pure
 * @phpstan-param mixed $applicative - this should implement some generic applicative interface
 * @phpstan-param mixed $applicativeF - this should implement some generic applicative interface
 * @phpstan-param mixed $applicativeG - this should implement some generic applicative interface
 *
 * @phpstan-return void
 */
function testApplicativeComposition(
    callable $assertEquals,
    callable $pure,
    $applicative,
    $applicativeF,
    $applicativeG
): void {
    $curriedComposition = Lambda::of(static function (callable $f, callable $g): callable {
        return compose($f, $g);
    });

    $assertEquals(
        $pure($curriedComposition)->ap($applicativeF)->ap($applicativeG)->ap($applicative),
        $applicativeF->ap($applicativeG->ap($applicative))
    );
}

interface CallSpy
{
    public function __invoke(): void;

    /** @phpstan-return array<int, array<mixed>> */
    public function getCalls(): array;
}

function createInvokableSpy(): CallSpy
{
    return new class implements CallSpy {
        /** @var array<int, array<mixed>> $calls */
        private $calls = [];

        public function __invoke(): void
        {
            $this->calls[] = func_get_args();
        }

        public function getCalls(): array
        {
            return $this->calls;
        }
    };
}
