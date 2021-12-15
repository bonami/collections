<?php

declare(strict_types=1);

namespace Bonami\Collection;

use Bonami\Collection\Exception\InvalidStateException;
use Closure;
use ReflectionFunction;

/**
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
     * @phpstan-param callable $callable      - closure to wrap and convert into curried closure
     * @phpstan-param int $expectedNumberOfArgs  - if ommited, number of arguments is detected
     *                                          with slight performance impact
     * @phpstan-param array<mixed> $applied   - applied arguments tracked for delayed full aplication of final argument
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
     * @template A
     * @template B
     * @template Z
     *
     * @param callable(A, B): Z $callable
     *
     * @return self<A, self<B, Z>>
     */
    public static function curry2(callable $callable): self
    {
        return $callable instanceof self ? $callable : new self($callable, 2);
    }

    /**
     * @template A
     * @template B
     * @template C
     * @template Z
     *
     * @param callable(A, B, C): Z $callable
     *
     * @return self<A, self<B, self<C, Z>>>
     */
    public static function curry3(callable $callable): self
    {
        return $callable instanceof self ? $callable : new self($callable, 3);
    }

    /**
     * Mapping over function is equivalent of composing functions
     *
     * @template A
     *
     * @param CurriedFunction<O, A> $callable
     *
     * @return CurriedFunction<I, A>
     */
    public function map(CurriedFunction $callable): CurriedFunction
    {
        return self::of(function ($arg) use ($callable) {
            return $callable($this($arg));
        });
    }

    /**
     * @phpstan-param I $arg
     *
     * @phpstan-return O
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
