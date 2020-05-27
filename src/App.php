<?php
declare(strict_types=1);

namespace Branch;

use Branch\Interfaces\Routing\RouterInterface;
use Branch\Container\Container;

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
        $this->set(self::class, $this);
        $this->set('env', $config['env']);
        $this->set('config', call_user_func(
            $config['config'],
            $this->get('env')
        ));
        $this->setMultiple($config['di']);
        $this->set('_branch.routing.defaultMiddleware', call_user_func(
            $config['middleware'],
            $this->get('env'),
            $this->get('config')
        ));
        $this->set('_branch.routing.routes', $config['routes']);

        $router = $this->get(RouterInterface::class);
        
        if (!$router->init()) {
            throw new \LogicException("Can't emit response");
        }
    
        require __DIR__ . '/helpers.php';
    }
}