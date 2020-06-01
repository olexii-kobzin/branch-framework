<?php
declare(strict_types=1);

namespace Branch;

use Branch\Interfaces\Routing\RouterInterface;
use Branch\Container\Container;
use Branch\Interfaces\Container\ContainerInterface;

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
        $this->set(ContainerInterface::class, $this);
        $this->set('env', $config['env']);
        $this->set('settings', call_user_func(
            $config['settings'],
            $this->get('env')
        ));
        $this->setMultiple($config['di']);
        $this->set('_branch.routing.defaultMiddleware', call_user_func(
            $config['middleware'],
            $this->get('env'),
            $this->get('settings')
        ));
        $this->set('_branch.routing.routes', $config['routes']);

        $router = $this->get(RouterInterface::class);
        
        if (!$router->init()) {
            throw new \LogicException("Can't emit response");
        }
    
        require __DIR__ . '/helpers.php';
    }
}