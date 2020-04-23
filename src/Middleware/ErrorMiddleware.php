<?php
declare(strict_types=1);

namespace Branch\Middleware;

use Branch\Interfaces\EnvInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Branch\Helpers\ErrorHelper;
use Throwable;

class ErrorMiddleware implements MiddlewareInterface
{
    protected ResponseInterface $response;

    public function __construct(ResponseInterface $response)
    {
        $this->response = $response;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            $response =  $handler->handle($request);
        } catch (Throwable $e) {
            $report = [
                'code' => $e->getCode(),
                'message' => $e->getMessage(),
            ];

            $report = ENV['APP_ENV'] === EnvInterface::ENV_DEV
                ? array_merge($report, [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTrace(),
                ])
                : $report;

            $response = $this->response->withHeader('Content-Type', 'application/json');
            $response = $response->withStatus(ErrorHelper::getHttpCode($e));
            $body = $response->getBody();
            $body->write(json_encode($report));
        }
        

        return $response;
    }
}