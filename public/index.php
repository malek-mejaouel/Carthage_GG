<?php

use App\Kernel;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return function (array $context) {
    $path = dirname(__DIR__).'/var/sessions/'.$context['APP_ENV'];
    if (!is_dir($path)) {
        mkdir($path, 0775, true);
    }
    ini_set('session.save_path', $path);
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
