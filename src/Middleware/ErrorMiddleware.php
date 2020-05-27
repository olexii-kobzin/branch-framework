<?php
declare(strict_types=1);

namespace Branch\Middleware;

use Branch\App;
use Branch\Interfaces\EnvInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;

class ErrorMiddleware implements MiddlewareInterface
{
    protected const HTTP_MIN_CODE = 100;
    protected const HTTP_MAX_CODE = 599;

    protected array $env;

    protected ResponseInterface $response;

    public function __construct(
        App $app,
        ResponseInterface $response
    )
    {
        $this->env = $app->get('env');
        $this->response = $response;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            $response = $handler->handle($request);
        } catch (\Throwable $e) {
            $report = $this->formReport($e);

            $response = $this->response->withHeader('Content-Type', 'application/json');
            $response = $response->withStatus($this->getHttpCode($e->getCode()));

            $body = $response->getBody();
            $body->write(json_encode($report));
        }

        return $response;
    }

    protected function formReport(\Throwable $e): array
    {
        $report = [
            'code' => $e->getCode(),
            'message' => $e->getMessage(),
        ];

        $report = $this->env['APP_ENV'] === EnvInterface::ENV_DEV
            ? array_merge($report, [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTrace(),
            ])
            : $report;

        return $report;
    }

    protected function getHttpCode($code)
    {
        $isHttpCode = is_integer($code) && $this->isAllowedHttpCode($code);

        return $isHttpCode ? $code : 500;
    }

    protected function isAllowedHttpCode(int $code): bool
    {
        return self::HTTP_MIN_CODE <= $code && $code <= self::HTTP_MAX_CODE;
    }
}