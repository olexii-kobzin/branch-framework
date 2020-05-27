<?php
declare(strict_types=1);

namespace Branch\Routing;

use Branch\Interfaces\Routing\RouteConfigBuilderInterface;

class RouteConfigBuilder implements RouteConfigBuilderInterface
{
    public function getGroupConfig(array $end, array $config): array
    {
        return $this->mergeConifg($end, $config);
    }

    public function getRouteConfig(array $end, array $config): array
    {
        $mergedConfig = $this->mergeConifg($end, $config);
        $mergedConfig['pattern'] = $this->buildPattern($mergedConfig['path']);

        return $mergedConfig;
    }

    protected function mergeConifg(array $end, array $config): array
    {
        $config = array_merge($config, [
            'path' => $this->mergePath($end, $config),
        ]);

        return array_merge_recursive(array_filter(
            $end,
            fn($key): bool => !in_array($key, ['path']), ARRAY_FILTER_USE_KEY
        ), $config);
    }

    protected function mergePath(array $old, array $new): string
    {
        $old = $old['path'] ?? '';
        $path = isset($new['path']) ? trim($old, '/').'/'.trim($new['path'], '/') : $old;

        return '/' . trim($path, '/');
    }

    protected function buildPattern(string $path): string
    {
        $pattern = [];
        $parts = explode('/', trim($path, '/'));

        foreach ($parts as $part) {
            if (substr($part, 0, 1) === ':') {
                $pattern[] = "(?'" . substr($part, 1)  . "'.+)";
            } else {
                $pattern[] = $part;
            }
        }

        return '/^\/' . implode('\/', $pattern) . '$/';
    }
}