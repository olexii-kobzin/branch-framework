<?php

use Branch\App;

if (!function_exists('container')) {
    function container() {
        return App::getInstance();
    }
}

if (!function_exists('env')) {
    function env() {
        return container()->get('env');
    }
}

if (!function_exists('settings')) {
    function settings() {
        return container()->get('settings');
    }
}