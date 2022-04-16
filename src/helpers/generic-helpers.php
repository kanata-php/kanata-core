<?php

use Psr\Container\ContainerInterface;
use Monolog\Logger;
use League\Flysystem\Filesystem;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Nyholm\Psr7\Factory\Psr17Factory;
use voku\helper\Hooks;
use Swoole\Process;

if (! function_exists('container')) {
    /**
     * @return ContainerInterface
     */
    function container(): ContainerInterface
    {
        global $container;
        return $container;
    }
}

if (! function_exists('config')) {
    /**
     * Get configuration set at the config directory.
     *
     * @param string $key
     * @param mixed|null $default
     * @return mixed
     */
    function config(string $key, mixed $default = null): mixed
    {
        return array_get(container()->config, $key, $default);
    }
}

if (! function_exists('logger')) {
    /**
     * @return Logger
     */
    function logger(): Logger
    {
        return container()->logger;
    }
}

if (! function_exists('filesystem')) {
    /**
     * @return Filesystem
     */
    function filesystem(): Filesystem
    {
        return container()->filesystem;
    }
}

if (! function_exists('json_response')) {
    /**
     * Prepares a Psr7 Response  in JSON format
     *
     * @param Response $response
     * @param string $status
     * @param int $statusCode
     * @param $message
     * @param $errors
     * @param $overrideData
     * @return Response
     */
    function json_response(
        Response $response,
        string   $status,
        int      $statusCode,
                 $message = null,
                 $errors = null,
                 $overrideData = null
    ): Response
    {
        $data = [
            'status' => $status,
        ];

        if ($errors) {
            $data['errors'] = $errors;
        }

        if ($message) {
            $data['message'] = $message;
        }

        if ($overrideData) {
            $data = $overrideData;
        }

        $factory = new Psr17Factory();
        $steam = $factory->createStream(json_encode($data));

        return $response->withBody($steam)->withStatus($statusCode)->withHeader('Content-Type', 'application/json');
    }
}

if (! function_exists('get_query_params')) {
    /**
     * Get Params from Request Query.
     *
     * @param Request $request
     *
     * @return array
     */
    function get_query_params(Request $request): array
    {
        $data = $request->getUri()->getQuery();

        $data = array_filter(explode('&', $data));

        $rearrangedData = [];
        foreach ($data as $item) {
            $item = explode('=', $item);
            $rearrangedData[$item[0]] = $item[1];
        }

        return $rearrangedData;
    }
}

if (! function_exists('handle_existing_pid')) {
    /**
     * Verify if there is an existing PID and offers to kill it in order to proceed.
     *
     * @param string $pid_file
     *
     * @return void
     */
    function handle_existing_pid(string $pid_file): void
    {
        function stop_existent_service($pid, $pid_file)
        {
            Process::kill($pid);
            sleep(1);
            unlink($pid_file);
        }

        if (file_exists($pid_file)) {
            $pid = (int)file_get_contents($pid_file);

            if (OVERWRITE_EXISTENT_SERVICE) {
                stop_existent_service($pid, $pid_file);
                return;
            }

            echo 'Server already running (PID ' . $pid . '), would you like to try anyways? [y,n]' . PHP_EOL;
            $confirmation = trim(fread(STDIN, 1));

            if (!in_array($confirmation, ['y', 'n'])) {
                echo 'Not valid answer, exiting...' . PHP_EOL;
                exit;
            }

            if ($confirmation === 'n') {
                echo 'Exiting...' . PHP_EOL;
                exit;
            }

            echo 'Removing PID file...' . PHP_EOL;
            stop_existent_service($pid, $pid_file);
        }
    }
}

if (! function_exists('add_filter')) {
    /**
     * @param string $hook
     * @param $callback
     * @return mixed
     */
    function add_filter(string $hook, $callback): mixed
    {
        return Hooks::getInstance()->add_filter($hook, $callback);
    }
}

if (! function_exists('apply_filters')) {
    /**
     * @param string $hook
     * @param mixed $params
     * @return mixed
     */
    function apply_filters(string $hook, mixed $params): mixed
    {
        return Hooks::getInstance()->apply_filters($hook, $params);
    }
}

if (! function_exists('add_action')) {
    /**
     * @param string $hook
     * @param $callback
     * @return mixed
     */
    function add_action(string $hook, $callback): mixed
    {
        return Hooks::getInstance()->add_action($hook, $callback);
    }
}

if (! function_exists('do_action')) {
    /**
     * @param string $hook
     * @param $callback
     * @return void
     */
    function do_action(string $hook, $callback): void
    {
        Hooks::getInstance()->do_action($hook, $callback);
    }
}

if (!function_exists('array_get')) {
    /**
     * Get an item from an array using "dot" notation.
     * This is from Illuminate helper functions.
     *
     * @param array $array
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function array_get(array $array, string $key, mixed $default = null): mixed
    {
        if (is_null($key)) return $array;
        if (isset($array[$key])) return $array[$key];

        foreach (explode('.', $key) as $segment) {
            if (!is_array($array) || !array_key_exists($segment, $array)) {
                return value($default);
            }
            $array = $array[$segment];
        }
        return $array;
    }
}

if (!function_exists('array_only')) {
    /**
     * Filter array alllowing only pointed keys.
     *
     * @param array $array
     * @param array $keys
     * @return mixed
     */
    function array_only(array $array, array $keys): array
    {
        $newArray = [];

        foreach ($array as $key => $value) {
            if (in_array($key, $keys)) {
                $newArray[$key] = $value;
            }
        }

        return $newArray;
    }
}