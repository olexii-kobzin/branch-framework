<?php
declare(strict_types=1);

namespace Branch\Middleware;

use Branch\App;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;

class MethodValidationMiddleware implements MiddlewareInterface
{
    protected App $app;

    public function __construct(App $app)
    {
        $this->app = $app;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $requestMethod = $request->getMethod();
        $actionMethods = $this->app->get('_branch.routing.action.methods');
        
        if ($actionMethods && !in_array($requestMethod, $actionMethods)) {
            // TODO: add http exception
            throw new \Exception('Method not allowed', StatusCodeInterface::STATUS_METHOD_NOT_ALLOWED);
        }
        
        $response = $handler->handle($request);

        return $response;
    }
}