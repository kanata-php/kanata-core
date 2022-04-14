<?php

require __DIR__ . '/vendor/autoload.php';

use SebastianBergmann\Version;

$version = new Version('1.0.0', __DIR__);

var_dump($version->getVersion());
