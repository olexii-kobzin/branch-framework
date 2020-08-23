<?php

use Branch\App;

if (!function_exists('app')) {
    function container() {
        return App::getInstance();
    }
}

if (!function_exists('env')) {
    function env($key): ?string {
        $env = container()->get('env');

        return isset($env['key']) ? $env['key'] : null;
    }
}