<?php
declare(strict_types=1);

namespace Branch\Middleware;

use Branch\Interfaces\Container\ContainerInterface;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;

class MethodValidationMiddleware implements MiddlewareInterface
{
    protected ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $requestMethod = $request->getMethod();
        $actionMethods = $this->container->get('_branch.routing.action.methods');
        
        if ($actionMethods && !in_array($requestMethod, $actionMethods)) {
            // TODO: add http exception
            throw new \Exception('Method not allowed', StatusCodeInterface::STATUS_METHOD_NOT_ALLOWED);
        }
        
        $response = $handler->handle($request);

        return $response;
    }
}