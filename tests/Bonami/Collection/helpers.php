<?php

namespace Bonami\Collection;

/**
 * @param callable(mixed, mixed): void $assertEquals
 * @param callable(mixed, mixed): bool $equals
 * @param mixed $a
 *
 * @return void
 */
function testEqualsReflexivity(callable $assertEquals, callable $equals, $a): void
{
    $assertEquals(true, $equals($a, $a));
}

/**
 * @phpstan-template T
 * @param callable(bool, bool): void $assertEquals
 * @param callable(mixed, mixed): bool $equals
 * @param T $a
 * @param T $b
 *
 * @return void
 */
function testEqualsSymmetry(callable $assertEquals, callable $equals, $a, $b): void
{
    $assertEquals(
        $equals($a, $b),
        $equals($b, $a)
    );
}

/**
 * @phpstan-template T
 * @param callable(bool, bool): void $assertEquals
 * @param callable(mixed, mixed): bool $equals
 * @param T $a
 * @param T $b
 * @param T $c
 *
 * @return void
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
 * @phpstan-template A
 * @phpstan-param callable(ArrayList<A>|Option<A>|TrySafe<A>, ArrayList<A>|Option<A>|TrySafe<A>): void $assertEquals
 * @phpstan-param ArrayList<A>|Option<A>|TrySafe<A> $functor - this should implement some generic functor interface
 *
 * @return void
 */
function testFunctorIdentity(callable $assertEquals, $functor): void
{
    $id = function ($a) {
        return $a;
    };
    $assertEquals(
        $functor,
        $functor->map($id)
    );
}

/**
 * @param callable(mixed, mixed): void $assertEquals
 * @param mixed $functor - this should implement some generic functor interface
 * @param callable(mixed): mixed $f
 * @param callable(mixed): mixed $g
 *
 * @return void
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
 * @param callable(mixed, mixed): void $assertEquals
 * @param callable(mixed): mixed $pure
 * @param mixed $applicative - this should implement some generic applicative interface
 *
 * @return void
 */
function testApplicativeIdentity(callable $assertEquals, callable $pure, $applicative): void
{
    $assertEquals(
        $pure(identity())->ap($applicative),
        $applicative
    );
}

/**
 * @param callable(mixed, mixed): void $assertEquals
 * @param callable(mixed): mixed $pure
 * @param mixed $value
 * @param callable(mixed): mixed $f
 *
 * @return void
 */
function testApplicativeHomomorphism(callable $assertEquals, callable $pure, $value, callable $f): void
{
    $assertEquals(
        $pure($f)->ap($pure($value)),
        $pure($f($value))
    );
}

/**
 * @param callable(mixed, mixed): void $assertEquals
 * @param callable(mixed): mixed $pure
 * @param mixed $value
 * @param mixed $applicativeF - this should implement some generic applicative interface
 *
 * @return void
 */
function testApplicativeInterchange(callable $assertEquals, callable $pure, $value, $applicativeF): void
{
    $assertEquals(
        $applicativeF->ap($pure($value)),
        $pure(applicator($value))->ap($applicativeF)
    );
}

/**
 * @param callable(mixed, mixed): void $assertEquals
 * @param callable(mixed): mixed $pure
 * @param mixed $applicative - this should implement some generic applicative interface
 * @param mixed $applicativeF - this should implement some generic applicative interface
 * @param mixed $applicativeG - this should implement some generic applicative interface
 *
 * @return void
 */
function testApplicativeComposition(
    callable $assertEquals,
    callable $pure,
    $applicative,
    $applicativeF,
    $applicativeG
): void {
    $curriedComposition = Lambda::of(function (callable $f, callable $g): callable {
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
    /** @return array<int, array<mixed>> */
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
