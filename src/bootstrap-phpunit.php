<?php

include_once __DIR__ . '/../vendor/autoload.php';

use Kanata\Services\Bootstrap;

const ROOT_FOLDER = __DIR__;
const SKIP_CONFIG_FOLDER = true;
const SKIP_PLUGINS_FOLDER = true;

Bootstrap::bootstrapPhpunit();
