<?php
declare(strict_types=1);

namespace Branch;

use Branch\Interfaces\Routing\RouterInterface;
use Branch\Container\Container;
use Branch\Container\Loader;

class App extends Container
{
    protected static ?self $instance = null;

    protected Loader $loader;

    protected string $basePath;

    protected function __construct()
    {
        $this->loader = new Loader($this);

        parent::__construct();
    }

    public static function getInstance(): self
    {
        if (!self::$instance) {
            self::$instance = new App();
        }

        return self::$instance;
    }


    public function init(string $basePath, array $env = []): void
    {
        $this->setBasePath($basePath);

        if (!$env) {
            $this->setEnv();
        }

        $this->bootsrap();

        $this->setDefinitions();

        $this->initRouter();
    }

    public function getEnvPath(): string
    {
        return $this->basePath . DIRECTORY_SEPARATOR . '.env';
    }

    public function getConfigPath(): string
    {
        return $this->basePath . DIRECTORY_SEPARATOR . 'config';
    }

    public function getRoutesPath(): string
    {
        return $this->basePath . DIRECTORY_SEPARATOR . 'routes';
    }

    public function getPublicPath(): string
    {
        return $this->basePath . DIRECTORY_SEPARATOR . 'public';
    }

    public function getStoragePath(): string
    {
        return $this->basePath . DIRECTORY_SEPARATOR . 'storage';
    }

    protected function bootsrap(): void
    {
        $this->set(self::class, $this);

        $this->initHelpers();

        $this->load();
    }

    protected function setDefinitions(): void
    {
        $this->setMultiple($this->get('di'));
    }

    protected function load(): void
    {
        $this->loader->loadConfigs();
        $this->loader->loadRoutes();
    }

    protected function initRouter(): void
    {
        $router = $this->get(RouterInterface::class);
        
        if (!$router->init()) {
            throw new \Exception("Can't emit response");
        }
    }

    protected function initHelpers(): void
    {
        require __DIR__ . '/helpers.php';
    }

    protected function setEnv(): void
    {
        $env = new Env($this->getEnvPath());

        $this->set('env', $env->get());
    }

    protected function setBasePath(string $basePath): void
    {
        $this->basePath = rtrim($basePath, '\/');

        $this->setPathsInContainer();
    }

    protected function setPathsInContainer(): void
    {
        $this->set('path.base', $this->basePath);
        $this->set('path.config', $this->getConfigPath());
        $this->set('path.routes', $this->getRoutesPath());
        $this->set('path.public', $this->getPublicPath());
        $this->set('path.storage', $this->getStoragePath());
    }
}