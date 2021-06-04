<?php

declare(strict_types=1);

namespace Bonami\Collection;

use Bonami\Collection\Exception\InvalidStateException;
use Closure;
use ReflectionException;
use ReflectionFunction;

final class Lambda
{

    /** @var callable */
    protected $callable;

    /** @var array<mixed> */
    protected $applied;

    /** @var int|null */
    protected $numberOfArgs;

    /**
     * @phpstan-param callable $callable      - closure to wrap and convert into curried closure
     * @phpstan-param int|null $numberOfArgs  - if ommited, number of arguments is detected
     *                                          with slight performance impact
     * @phpstan-param array<mixed> $applied   - applied arguments tracked for delayed full aplication of final argument
     */
    protected function __construct(callable $callable, ?int $numberOfArgs = null, array $applied = [])
    {
        $this->callable = $callable;
        $this->numberOfArgs = $numberOfArgs;
        $this->applied = $applied;
    }

    public static function of(callable $callable): self
    {
        return $callable instanceof self ? $callable : new self($callable);
    }

    /**
     * Use this method instead of Lambda::of, if you dont want to use reflection
     * to determine number of callable arguments.
     *
     * Calling ReflectionMethod can have some performance impact.
     */
    public static function fromCallableWithNumberOfArgs(callable $callable, int $numberOfArgs): self
    {
        $isNumberOfArgsInvalid = $callable instanceof self
            && $callable->numberOfArgs !== null
            && ($callable->numberOfArgs - count($callable->applied) !== $numberOfArgs);

        if ($isNumberOfArgsInvalid) {
            throw new InvalidStateException('Passed number of arguments seems to be invalid');
        }

        return $callable instanceof self ? $callable : new self($callable, $numberOfArgs);
    }

    /**
     * Mapping over function is equivalent of composing functions
     */
    public function map(callable $callable): Lambda
    {
        return new self(compose($callable, $this->callable));
    }

    /**
     * @phpstan-param mixed... $args
     *
     * @phpstan-return mixed
     */
    public function __invoke(...$args)
    {
        if ($this->numberOfArgs === null) {
            $this->numberOfArgs = (new ReflectionFunction(
                Closure::fromCallable($this->callable)
            ))->getNumberOfParameters();
        }
        $newApplied = $this->applied;
        foreach ($args as $arg) {
            $newApplied[] = $arg;
        }
        $numberOfArgsLeft = $this->numberOfArgs - count($newApplied);

        return $numberOfArgsLeft > 0
            ? new self($this->callable, $this->numberOfArgs, $newApplied)
            : ($this->callable)(...$newApplied);
    }
}
