<?php

use Branch\App;
use Branch\Interfaces\EnvInterface;
use Branch\Interfaces\Routing\RouterInterface;

if (!function_exists('app')) {
    function app() {
        return App::getInstance();
    }
}

if (!function_exists('config')) {
    function config() {
        return app()->get('config');
    }
}