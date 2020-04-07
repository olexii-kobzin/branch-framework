<?php
declare(strict_types=1);

namespace Branch\Routing;

class RouteCollector
{
    public static function mergeConifg($end, $config): array
    {
        $config = array_merge($config, [
            'path' => static::mergePath($end, $config),
        ]);

        return array_merge_recursive(array_filter(
            $end, fn($key) => !in_array($key, ['path']), ARRAY_FILTER_USE_KEY
        ), $config);
    }

    public static function mergePath($old, $new)
    {
        $old = $old['path'] ?? '';

        $path = isset($new['path']) ? trim($old, '/').'/'.trim($new['path'], '/') : $old;

        return trim($path, '/');
    }
}