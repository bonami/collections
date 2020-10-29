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
     * @param callable $callable
     * @param int|null $numberOfArgs
     * @param array<mixed> $applied
     */
    protected function __construct(callable $callable, ?int $numberOfArgs = null, array $applied = [])
    {
         $this->callable = $callable;
         $this->numberOfArgs = $numberOfArgs;
         $this->applied = $applied;
    }

    public static function of(callable $callable): Lambda
    {
         return $callable instanceof Lambda ? $callable : new Lambda($callable);
    }

    /**
     * Use this method instead of Lambda::of, if you dont want to use reflection
     * to determine number of callable arguments.
     *
     * Calling ReflectionMethod can have some performance impact.
     */
    public static function fromCallableWithNumberOfArgs(callable $callable, int $numberOfArgs): Lambda
    {
         $isNumberOfArgsInvalid = $callable instanceof Lambda
         && $callable->numberOfArgs !== null
         && (($callable->numberOfArgs - count($callable->applied)) !== $numberOfArgs);

        if ($isNumberOfArgsInvalid) {
              throw new InvalidStateException("Passed number of arguments seems to be invalid");
        }

        return $callable instanceof Lambda ? $callable : new Lambda($callable, $numberOfArgs);
    }

    /**
     * Mapping over function is equivalent of composing functions
     */
    public function map(callable $callable): Lambda
    {
         return new static(compose($callable, $this->callable));
    }

    /**
     * @param mixed... $args
     *
     * @return mixed
     * @throws ReflectionException
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

        return ($numberOfArgsLeft > 0)
         ? new static($this->callable, $this->numberOfArgs, $newApplied)
         : ($this->callable)(...$newApplied);
    }
}
