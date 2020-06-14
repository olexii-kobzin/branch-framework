<?php
declare(strict_types=1);

namespace Branch;

use Branch\Interfaces\Routing\RouterInterface;
use Branch\Container\Container;
use Branch\Container\DefinitionInfo;
use Branch\Container\Invoker;
use Branch\Container\Resolver;

class App extends Container
{
    protected static ?self $instance = null;

    protected array $config;

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

    public function init(array $config): void
    {
        $this->setupContainer();

        $this->set(self::class, $this);
        $this->set('env', $config['env']);
        $this->set('settings', $config['settings']($this->get('env')));

        $this->initHelpers();

        $this->setMultiple($config['di']);
        $this->set('_branch.routing.defaultMiddleware', $config['middleware'](
            $this->get('env'),
            $this->get('settings')
        ));
        $this->set('_branch.routing.routes', $config['routes']);

        $this->initRouter();
    }

    private function setupContainer(): void
    {
        $this->setDefiniionInfo(new DefinitionInfo());
        
        $this->setResolver(new Resolver($this, $this->definitionInfo));

        $this->setInvoker(new Invoker($this->resolver));
    }

    private function initRouter()
    {
        $router = $this->get(RouterInterface::class);
        
        if (!$router->init()) {
            throw new \Exception("Can't emit response");
        }
    }

    private function initHelpers()
    {
        require __DIR__ . '/helpers.php';
    }
}