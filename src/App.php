<?php
declare(strict_types=1);

namespace Branch;

use Branch\Interfaces\Container\ContainerInterface;
use Branch\Interfaces\Routing\RouterInterface;
use Branch\Container\Container;

class App extends Container
{
    protected static ?self $instance = null;

    protected string $configFolder = '';

    protected string $routesFolder = '';

    protected function __construct() {
        parent::__construct();
    }

    public static function getInstance(): self
    {
        if (!static::$instance) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    public function init(string $configFolder, string $routesFolder)
    {
        $this->configFolder = $configFolder;
        $this->routesFolder = $routesFolder;

        $this->set(App::class, $this);
        $this->set('config', $this->getConfig());
        $this->setMultiple($this->getDi());
        $this->set('_sys.routing.defaultMiddleware', $this->getDefaultMiddleware());
        $this->set('_sys.routing.routes', $this->getRoutes());

        $router = $this->get(RouterInterface::class);
        $router->init();

        require __DIR__ . '/helpers.php';
    }

    public function getConfigFolder(): string
    {
        return $this->configFolder;
    }

    public function getRoutesFolder(): string
    {
        return $this->routesFolder;
    }

    protected function getConfig(): array
    {
        $path = implode(DIRECTORY_SEPARATOR, [
            $this->configFolder,
            'config.php',
        ]);

        return require $path;
    }

    protected function getDi(): array
    {
        $path = implode(DIRECTORY_SEPARATOR, [
            $this->configFolder,
            'di.php',
        ]);

        return require $path;
    }

    protected function getDefaultMiddleware(): array
    {
        $path = implode(DIRECTORY_SEPARATOR, [
            $this->configFolder,
            'middleware.php',
        ]);

        return require $path;
    }

    protected function getRoutes()
    {
        $path = implode(DIRECTORY_SEPARATOR, [
            $this->routesFolder,
            'index.php',
        ]);

        return require $path;
    }

}