<?php declare(strict_types = 1);

namespace jschreuder\Middle;

use Interop\Http\Middleware\DelegateInterface;
use Interop\Http\Middleware\ServerMiddlewareInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

final class ErrorHandlerMiddleware implements ServerMiddlewareInterface
{
    /** @var  LoggerInterface */
    private $logger;

    /** @var  callable */
    private $errorController;

    public function __construct(LoggerInterface $logger, callable $errorController)
    {
        $this->logger = $logger;
        $this->errorController = $errorController;
    }

    public function process(ServerRequestInterface $request, DelegateInterface $delegate) : ResponseInterface
    {
        try {
            return $delegate->next($request);
        } catch (\Throwable $exception) {
            $this->logger->alert($exception->getMessage(), [
                'line' => $exception->getLine(),
                'file' => $exception->getFile(),
                'backtrace' => $exception->getTrace()
            ]);
            return ($this->errorController)($exception, $request);
        }
    }
}
