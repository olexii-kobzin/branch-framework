<?php
declare(strict_types=1);

namespace Branch;

use Branch\Interfaces\Container\ContainerInterface;
use Branch\Interfaces\Routing\RouterInterface;
use Branch\Container\Container;

class App
{
    protected static ?self $instance = null;

    protected function __construct() {}

    public static function getInstance(): self
    {
        if (!static::$instance) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    public function init()
    {
        $this->container = new Container();
        $this->container->build();
        $router = $this->container->get(RouterInterface::class);
        $router->init();

        require __DIR__ . '/helpers.php';
    }

    public function getContainer(): ContainerInterface
    {
        return $this->container;
    }

}