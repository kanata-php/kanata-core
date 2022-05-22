<?php


namespace Kanata\Services;

use Mockery\Matcher\Closure;

/**
 * Proxy Design Pattern.
 *
 * User here to allow us to intercept.
 *
 * Reference: https://refactoring.guru/design-patterns/proxy
 * PHP Reference: https://refactoring.guru/design-patterns/proxy/php/example
 */

class Proxy
{
    protected mixed $instance;

    protected mixed $mockInstance = null;

    /**
     * Facade constructor.
     *
     * @param mixed $class Class instance or closure.
     */
    public function __construct(mixed $class = null)
    {
        if (null === $class) {
            return;
        }

        $this->instance = $class;
    }

    public function setInstance(mixed $instance): void
    {
        $this->instance = $instance;
    }

    public function __invoke()
    {
        call_user_func_array($this->instance, func_get_args());
    }

    public function __call(string $name, array $arguments)
    {
        call_user_func_array([$this->instance, $name], $arguments);
    }
}
