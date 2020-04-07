<?php

use Branch\App;
use Branch\Interfaces\ConfigInterface;
use Branch\Interfaces\Routing\RouterInterface;

if (!function_exists('app')) {
    function app() {
        return App::getInstance();
    }
}

if (!function_exists('container')) {
    function container() {
        $app = app();
        return $app->getContainer();
    }
}

if (!function_exists('config')) {
    function config() {
        $container = container();
        return $container->get(ConfigInterface::class);
    }
}

if (!function_exists('router')) {
    function router() {
        $container = container();
        return $container->get(RouterInterface::class);
    }
}