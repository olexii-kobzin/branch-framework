<?php

use Branch\App;
use Branch\Interfaces\Routing\RouterInterface;

if (!function_exists('app')) {
    function app() {
        return App::getInstance();
    }
}

if (!function_exists('router')) {
    function router() {
        return app()->get(RouterInterface::class);
    }
}