<?php

namespace Bonami\Collection;

function testEqualsReflexivity(callable $assertEquals, callable $equals, $a): void {
	$assertEquals(true, $equals($a, $a));
}

function testEqualsSymmetry(callable $assertEquals, callable $equals, $a, $b): void {
	$assertEquals(
		$equals($a, $b),
		$equals($b, $a)
	);
}

function testEqualsTransitivity(callable $assertEquals, callable $equals, $a, $b, $c): void {
	$assertEquals(
		$equals($a, $b) && $equals($b, $c),
		$equals($a, $c)
	);
}

/* @see https://wiki.haskell.org/Functor#Functor_Laws */

function testFunctorIdentity(callable $assertEquals, $functor): void {
	$assertEquals(
		$functor,
		$functor->map(identity())
	);
}

function testFunctorComposition(callable $assertEquals, $functor, callable $f, callable $g): void {
	$assertEquals(
		$functor->map($g)->map($f),
		$functor->map(compose($f, $g))
	);
}


/* @see https://en.wikibooks.org/wiki/Haskell/Applicative_functors#Applicative_functor_laws */

function testApplicativeIdentity(callable $assertEquals, callable $pure, $applicative): void {
	$assertEquals(
		$pure(identity())->ap($applicative),
		$applicative
	);
}

function testApplicativeHomomorphism(callable $assertEquals, callable $pure, $value, callable $f): void {
	$assertEquals(
		$pure($f)->ap($pure($value)),
		$pure($f($value))
	);
}

function testApplicativeInterchange(callable $assertEquals, callable $pure, $value, $applicativeF): void {
	$assertEquals(
		$applicativeF->ap($pure($value)),
		$pure(applicator($value))->ap($applicativeF)
	);
}

function testApplicativeComposition(callable $assertEquals, callable $pure, $applicative, $applicativeF, $applicativeG): void {
	$curriedComposition = Lambda::of(function (callable $f, callable $g): callable {
		return compose($f, $g);
	});

	$assertEquals(
		$pure($curriedComposition)->ap($applicativeF)->ap($applicativeG)->ap($applicative),
		$applicativeF->ap($applicativeG->ap($applicative))
	);
}

function createInvokableSpy() {
	return new class {
		private $calls = [];
		public function __invoke() {
			$this->calls[] = func_get_args();
		}
		public function getCalls(): array {
			return $this->calls;
		}
	};
}
