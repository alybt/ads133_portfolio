<?php

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
require_once dirname(__DIR__, 2) . '/src/config/config.php';

header('Content-Type: text/plain; charset=utf-8');

test_connection();