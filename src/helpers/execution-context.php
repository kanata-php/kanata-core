<?php

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutputInterface;

if (! function_exists('get_output')) {
    function get_output(): ConsoleOutputInterface
    {
        return container()->output;
    }
}

if (! function_exists('get_input')) {
    function get_input(): InputInterface
    {
        if (!container()->has('input')) {
            return new ArgvInput;
        }

        return container()->input;
    }
}

if (! function_exists('is_websocket_execution')) {
    /**
     * Says if the current execution is websocket context.
     *
     * @return bool
     */
    function is_websocket_execution(): bool
    {
        if (!get_input()->hasOption('websocket')) {
            return false;
        }

        return get_input()->getOption('websocket');
    }
}

if (! function_exists('is_http_execution')) {
    /**
     * Says if the current execution is http context.
     * @return bool
     */
    function is_http_execution(): bool
    {
        return !is_websocket_execution()
            && !is_queue_execution();
    }
}

if (! function_exists('is_queue_execution')) {
    /**
     * Says if the current execution is queue context.
     *
     * @return bool
     */
    function is_queue_execution(): bool
    {
        if (!get_input()->hasOption('queue')) {
            return false;
        }

        return get_input()->getOption('queue');
    }
}

if (! function_exists('is_shell_execution')) {
    /**
     * Says if the current execution is shell (psyshell) context.
     *
     * @return bool
     */
    function is_shell_execution(): bool
    {
        return get_input()->getFirstArgument() === 'shell';
    }
}
