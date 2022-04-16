<?php

if (!defined('ROOT_FOLDER')) {
    define('ROOT_FOLDER', '');
}

if (!defined('APP_NAME')) {
    define('APP_NAME', env('APP_NAME', false));
}

if (!defined('HTTP_SERVER_HOST')) {
    define('HTTP_SERVER_HOST', env('HTTP_SERVER_HOST', '0.0.0.0'));
}

if (!defined('HTTP_SERVER_PORT')) {
    define('HTTP_SERVER_PORT', env('HTTP_SERVER_PORT', 8001));
}

if (!defined('HTTP_SERVER_SSL')) {
    define('HTTP_SERVER_SSL', env('HTTP_SERVER_SSL', false));
}

if (!defined('SSL_CERTIFICATE')) {
    define('SSL_CERTIFICATE', env('SSL_CERTIFICATE', ''));
}

if (!defined('SSL_KEY')) {
    define('SSL_KEY', env('SSL_KEY', ''));
}

if (!defined('WS_SERVER_HOST')) {
    define('WS_SERVER_HOST', env('WS_SERVER_HOST', '0.0.0.0'));
}

if (!defined('WS_SERVER_PORT')) {
    define('WS_SERVER_PORT', env('WS_SERVER_PORT', 8002));
}

if (!defined('WS_SERVER_SSL')) {
    define('WS_SERVER_SSL', env('WS_SERVER_SSL', false));
}

if (!defined('WS_SSL_CERTIFICATE')) {
    define('WS_SSL_CERTIFICATE', env('WS_SSL_CERTIFICATE', ''));
}

if (!defined('WS_SSL_KEY')) {
    define('WS_SSL_KEY', env('WS_SSL_KEY', ''));
}

if (!defined('WS_TICK_ENABLED')) {
    /**
     * This serves for ticks to run on every few seconds and deal with messages
     * available at the communication protocol.
     */
    define('WS_TICK_ENABLED', env('WS_TICK_ENABLED', false));
}

if (!defined('WS_TICK_INTERVAL')) {
    /**
     * The WS Tick interval.
     */
    define('WS_TICK_INTERVAL', env('WS_TICK_INTERVAL', 1000));
}

if (!defined('WS_MESSAGE_ACTION')) {
    /**
     * This serves for communication between services with the ws server.
     */
    define('WS_MESSAGE_ACTION', env('WS_MESSAGE_ACTION', 'wsmessage'));
}

if (!defined('HTTP_PORT_PARAM')) {
    define('HTTP_PORT_PARAM', 'port');
}

if (!defined('WEBSOCKET_PORT_PARAM')) {
    define('WEBSOCKET_PORT_PARAM', 'wsport');
}

if (!defined('PID_FILE')) {
    define('PID_FILE', env('PID_FILE', './http-server-pid'));
}

if (!defined('WS_PID_FILE')) {
    define('WS_PID_FILE', env('WS_PID_FILE', './ws-server-pid'));
}

if (!defined('QUEUE_SERVER_HOST')) {
    define('QUEUE_SERVER_HOST', env('QUEUE_SERVER_HOST', 'rabbitmq'));
}

if (!defined('QUEUE_SERVER_PORT')) {
    define('QUEUE_SERVER_PORT', env('QUEUE_SERVER_PORT', 5672));
}

if (!defined('QUEUE_SERVER_USER')) {
    define('QUEUE_SERVER_USER', env('QUEUE_SERVER_USER', 'guest'));
}

if (!defined('QUEUE_SERVER_PASSWORD')) {
    define('QUEUE_SERVER_PASSWORD', env('QUEUE_SERVER_PASSWORD', 'guest'));
}

if (!defined('DEFAULT_QUEUE')) {
    define('DEFAULT_QUEUE', env('DEFAULT_QUEUE', false));
}

if (!defined('OVERWRITE_EXISTENT_SERVICE')) {
    /**
     * Overwrite or not an existent service using the same port.
     */
    define('OVERWRITE_EXISTENT_SERVICE', env('OVERWRITE_EXISTENT_SERVICE', true));
}

if (!defined('WEBSOCKET_CONSOLE_OPTION')) {
    /**
     * Specify websocket context.
     */
    define('WEBSOCKET_CONSOLE_OPTION', 'websocket');
}

if (!defined('QUEUE_CONSOLE_OPTION')) {
    /**
     * Specify queue context.
     */
    define('QUEUE_CONSOLE_OPTION', 'queue');
}

if (!defined('QUEUE_NAME_CONSOLE_OPTION')) {
    /**
     * Specify the name of the queue being executed in the current context.
     */
    define('QUEUE_NAME_CONSOLE_OPTION', 'queue-name');
}

if (!defined('FRESH_CONSOLE_OPTION')) {
    /**
     * Specify that "start-kanata" will run migrations fresh.
     */
    define('FRESH_CONSOLE_OPTION', 'fresh');
}

if (!defined('FRESH_PLUGINS_CONSOLE_OPTION')) {
    /**
     * Specify that "start-kanata" will run migrations fresh including plugins.
     */
    define('FRESH_PLUGINS_CONSOLE_OPTION', 'fresh-plugins');
}

// -----------------------------------------------------
// JSON db
// -----------------------------------------------------

if (!defined('LAZER_DATA_PATH')) {
    define('LAZER_DATA_PATH', ROOT_FOLDER . env('LAZER_DATA_PATH', '/data/'));
}

// -----------------------------------------------------
// MySQL db
// -----------------------------------------------------

if (!defined('DB_DRIVER')) {
    define('DB_DRIVER', env('DB_DRIVER', 'mysql'));
}

if (!defined('DB_HOST')) {
    define('DB_HOST', env('DB_HOST', 'localhost'));
}

if (!defined('DB_PORT')) {
    define('DB_PORT', env('DB_PORT', 3306));
}

if (!defined('DB_DATABASE')) {
    define('DB_DATABASE', env('DB_DATABASE', 'kanata'));
}

if (!defined('DB_USERNAME')) {
    define('DB_USERNAME', env('DB_USERNAME', 'root'));
}

if (!defined('DB_PASSWORD')) {
    define('DB_PASSWORD', env('DB_PASSWORD', 'secret'));
}

if (!defined('DB_CHARSET')) {
    define('DB_CHARSET', env('DB_CHARSET', 'utf8'));
}

if (!defined('DB_COLLATION')) {
    define('DB_COLLATION', env('DB_COLLATION', 'utf8_unicode_ci'));
}

if (!defined('DB_PREFIX')) {
    define('DB_PREFIX', env('DB_PREFIX', ''));
}
