<?php declare(strict_types=1);

namespace jschreuder\Middle\ServerMiddleware;

use jschreuder\Middle\Controller\ControllerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

final class ErrorHandlerMiddleware implements MiddlewareInterface
{
    private LoggerInterface $logger;
    private ControllerInterface $errorController;

    public function __construct(LoggerInterface $logger, ControllerInterface $errorController)
    {
        $this->logger = $logger;
        $this->errorController = $errorController;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $requestHandler): ResponseInterface
    {
        try {
            return $requestHandler->handle($request);
        } catch (\Throwable $exception) {
            $this->logger->alert($exception->getMessage(), [
                'line' => $exception->getLine(),
                'file' => $exception->getFile(),
                'backtrace' => $exception->getTrace()
            ]);
            return $this->errorController->execute($request->withAttribute('error', $exception));
        }
    }
}
