<?php

declare(strict_types=1);

$path = dirname(__DIR__) . '/cartridges/bm_venny_setupwizard/public/style.css';
if (!is_file($path)) {
    http_response_code(404);
    exit;
}

header('Content-Type: text/css; charset=utf-8');
readfile($path);
