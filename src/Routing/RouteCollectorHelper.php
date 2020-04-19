<?php
declare(strict_types=1);

namespace Branch\Routing;

class RouteCollectorHelper
{
    public static function getGroupConfig(array $end, array $config): array
    {
        return static::mergeConifg($end, $config);
    }

    public static function getRouteConfig(array $end, array $config): array
    {
        $mergedConfig = static::mergeConifg($end, $config);
        $mergedConfig['pattern'] = static::buildPattern($mergedConfig['path']);

        return $mergedConfig;
    }

    protected static function mergeConifg(array $end, array $config): array
    {
        $config = array_merge($config, [
            'path' => static::mergePath($end, $config),
        ]);

        return array_merge_recursive(array_filter(
            $end, fn($key) => !in_array($key, ['path']), ARRAY_FILTER_USE_KEY
        ), $config);
    }

    protected static function mergePath(array $old, array $new): string
    {
        $old = $old['path'] ?? '';
        $path = isset($new['path']) ? trim($old, '/').'/'.trim($new['path'], '/') : $old;

        return trim($path, '/');
    }

    protected static function buildPattern(string $path): string
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

        return '/^' . implode('\/', $pattern) . '$/';
    }
}