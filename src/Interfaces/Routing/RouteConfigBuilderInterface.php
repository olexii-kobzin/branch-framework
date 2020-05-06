<?php
namespace Branch\Interfaces\Routing;

interface RouteConfigBuilderInterface
{
    public function getGroupConfig(array $end, array $config): array;

    public function getRouteConfig(array $end, array $config): array;
}