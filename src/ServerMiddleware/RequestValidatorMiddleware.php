<?php declare(strict_types=1);

namespace jschreuder\Middle\ServerMiddleware;

use jschreuder\Middle\Controller\RequestValidatorInterface;
use jschreuder\Middle\Exception\ValidationFailedException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class RequestValidatorMiddleware implements MiddlewareInterface
{
    private \Closure $errorHandler;

    public function __construct(\Closure $errorHandler)
    {
        $this->errorHandler = $errorHandler;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $requestHandler): ResponseInterface
    {
        $controller = $request->getAttribute('controller');

        if ($controller instanceof RequestValidatorInterface) {
            // Execute the request-validator from the controller
            try {
                $controller->validateRequest($request);
            } catch (ValidationFailedException $exception) {
                // Hand of to error-handler on failure
                return ($this->errorHandler)($request, $exception);
            }
        }

        // Filtered and validated (if applicable), let's continue on
        return $requestHandler->handle($request);
    }
}
