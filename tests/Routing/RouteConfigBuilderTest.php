<?php
declare(strict_types=1);

use Branch\Interfaces\Routing\RouteConfigBuilderInterface;
use Branch\Routing\RouteConfigBuilder;
use Branch\Tests\BaseTestCase;

class RouteConfigBuilderTest extends BaseTestCase
{
    protected static RouteConfigBuilder $routeConfigBuilder;

    public static function setUpBeforeClass(): void
    {
        self::$routeConfigBuilder = new RouteConfigBuilder();
    }

    public function testGroupConfigReturnsAnArray(): void
    {
        $config1 = ['path' => 'path-part-1'];
        $config2 = ['path' => 'path-part-2'];

        $config = self::$routeConfigBuilder->getGroupConfig($config1, $config2);

        $this->assertIsArray($config);
    }

    public function testGroupConfigThrowsAnErrorIfNotArrayProvided(): void
    {
        $config1 = ['path' => 'path-part-1'];
        $config2 = null;

        $this->expectException(\Error::class);

        self::$routeConfigBuilder->getGroupConfig($config1, $config2);
    }

    public function testRouteConfigReturnsArray(): void
    {
        $config1 = ['path' => 'path-part-1'];
        $config2 = ['path' => 'path-part-2'];

        $config = self::$routeConfigBuilder->getRouteConfig($config1, $config2);

        $this->assertIsArray($config);
    }

    public function testRouteConfigThrowsAnErrorIfNotArrayProvided(): void
    {
        $config1 = ['path' => 'path-part-1'];
        $config2 = null;

        $this->expectException(\Error::class);

        self::$routeConfigBuilder->getRouteConfig($config1, $config2);
    }

    public function testMergedConfigUsesMergedPath(): void
    {
        $config1 = ['path' => 'path-part-1'];
        $config2 = ['path' => 'path-part-2'];

        $mergeConfigMethodReflection = $this->getMethodReflection(RouteConfigBuilder::class, 'mergeConifg');

        $mergedConfig = $mergeConfigMethodReflection->invokeArgs(self::$routeConfigBuilder, [$config1, $config2]);

        $this->assertEquals('/path-part-1/path-part-2', $mergedConfig['path']);
    }

    public function testConfigMergedRecursively(): void
    {
        $config1 = [
            'middleware' => [
                'MiddlewareClass1',
                'MiddlewareClass2',
            ]
        ];
        $config2 = [
            'middleware' => [
                'MiddlewareClass3',
                'MiddlewareClass4',
            ]
        ];

        $mergeConfigMethodReflection = $this->getMethodReflection(RouteConfigBuilder::class, 'mergeConifg');

        $mergedConfig = $mergeConfigMethodReflection->invokeArgs(self::$routeConfigBuilder, [$config1, $config2]);

        $this->assertTrue(empty(array_diff(
            $mergedConfig['middleware'],
            [
                'MiddlewareClass1',
                'MiddlewareClass2',
                'MiddlewareClass3',
                'MiddlewareClass4',
            ],
        )));
    }

    public function testCorrectPathIsBuiltWithOneEmptyPath(): void
    {
        $config1 = ['path' => 'path-part-1'];
        $config2 = ['path' => 'path-part-2'];
        $config3 = [];

        $mergePathMethodReflection = $this->getMethodReflection(RouteConfigBuilder::class, 'mergePath');

        $path1 = $mergePathMethodReflection->invokeArgs(self::$routeConfigBuilder, [$config1, $config3]);
        $path2 = $mergePathMethodReflection->invokeArgs(self::$routeConfigBuilder, [$config3, $config2]);

        $this->assertEquals('/path-part-1', $path1);
        $this->assertEquals('/path-part-2', $path2);
    }

    public function tesetCorrectPathIsBuiltWithBothEmptyPaths(): void
    {
        $mergePathMethodReflection = $this->getMethodReflection(RouteConfigBuilder::class, 'mergePath');

        $path = $mergePathMethodReflection->invokeArgs(self::$routeConfigBuilder, [[], []]);

        $this->assertEquals('/', $path);
    }

    public function testCorrectPatternIsBuilt(): void
    {
        $buildPatternMethodReflection = $this->getMethodReflection(RouteConfigBuilder::class, 'buildPattern');

        $pattern = $buildPatternMethodReflection->invokeArgs(self::$routeConfigBuilder, [
            'path-part-1/path-part-2',
        ]);

        $this->assertEquals(
            '/^\/path-part-1\/path-part-2$/',
            $pattern
        );
    }

    public function testCorrectPatternIsBuiltWithParameters(): void
    {
        $buildPatternMethodReflection = $this->getMethodReflection(RouteConfigBuilder::class, 'buildPattern');

        $pattern = $buildPatternMethodReflection->invokeArgs(self::$routeConfigBuilder, [
            'path-part-1/:arg1/path-part-2/:arg2',
        ]);

        $this->assertEquals(
            "/^\/path-part-1\/(?'arg1'.+)\/path-part-2\/(?'arg2'.+)$/",
            $pattern
        );
    }
}