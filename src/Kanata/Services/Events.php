<?php

namespace Kanata\Services;

use Kanata\Interfaces\EventInterface;
use OpenSwoole\Timer;

class Events
{
    /**
     * List of event listeners with its callbacks.
     *
     * @var array [EventInterface::class => callable[]]
     */
    protected array $listeners = [];

    protected function __construct()
    {
    }

    public static function getInstance(): self
    {
        static $instance;

        if (null === $instance) {
            $instance = new self();
        }

        return $instance;
    }

    public static function addEventListener(string $event, callable $callback): void
    {
        if (!isset(self::getInstance()->listeners[$event])) {
            self::getInstance()->listeners[$event] = [];
        }

        self::getInstance()->listeners[$event][] = $callback;
    }

    public static function dispatch(EventInterface $event): void
    {
        $listeners = array_get(self::getInstance()->listeners, $event::class, []);

        for ($i = 0; $i < count($listeners); $i++) {
            // let's avoid calling pcntl_fork procedures at psyshell.
            if (is_shell_execution()) {
                self::callThroughProxy('call_user_func', [$listeners[$i], $event]);
                continue;
            }

            self::callThroughProxy(
                Timer::class,
                [1, 'call_user_func', $listeners[$i], $event],
                'after'
            );
        }
    }

    public static function dispatchNow(EventInterface $event): void
    {
        $listeners = array_get(self::getInstance()->listeners, $event::class, []);

        for ($i = 0; $i < count($listeners); $i++) {
            self::callThroughProxy('call_user_func', [$listeners[$i], $event]);
        }
    }

    /**
     * This method is useful to allow mocks.
     *
     * @param $instance
     * @param array $params
     * @param string|null $method
     * @return void
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    private static function callThroughProxy($instance, array $params, ?string $method = null)
    {
        $proxy = container()->get('proxy');
        $proxy->setInstance($instance);

        if (null !== $method) {
            call_user_func_array([$proxy, $method], $params);
            return;
        }

        call_user_func_array($proxy, $params);
    }
}
