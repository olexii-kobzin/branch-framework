<?php
declare(strict_types=1);

namespace Branch\Container;

use Branch\App;
use Branch\Interfaces\Container\LoaderInterface;

class Loader implements LoaderInterface
{
    protected Container $app;

    public function __construct(App $app)
    {
        $this->app = $app;
    }

    public function loadConfigs(): void
    {
        $this->load($this->app->get('path.config'));
    }

    public function loadRoutes(): void
    {
        $this->load($this->app->get('path.routes'), 'routes');
    }

    protected function load(string $dir, ?string $prefix = null): void
    {
        foreach (new \DirectoryIterator($dir) as $fileInfo) {
            if ($fileInfo->isFile()) {
                $name = pathinfo($fileInfo->getFilename(), PATHINFO_FILENAME);
                $prefixedName = $prefix ? $prefix . '.' . $name : $name;
                $this->app->set($prefixedName, require $fileInfo->getRealPath());
            }
        }
    }
}