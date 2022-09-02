<?php

include_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use Kanata\Services\Bootstrap;

const ROOT_FOLDER = __DIR__;
const SKIP_CONFIG_FOLDER = true;
const SKIP_PLUGINS_FOLDER = true;

$dotenv = Dotenv::createImmutable(__DIR__, '/../.env.testing');
$dotenv->load();

Bootstrap::bootstrapPhpunit();

