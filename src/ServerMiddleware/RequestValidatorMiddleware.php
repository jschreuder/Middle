<?php declare(strict_types = 1);

namespace jschreuder\Middle\ServerMiddleware;

use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use jschreuder\Middle\Controller\RequestValidatorInterface;
use jschreuder\Middle\Exception\ValidationFailedException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class RequestValidatorMiddleware implements MiddlewareInterface
{
    /** @var  callable */
    private $errorHandler;

    public function __construct(callable $errorHandler)
    {
        $this->errorHandler = $errorHandler;
    }

    public function process(ServerRequestInterface $request, DelegateInterface $delegate): ResponseInterface
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
        return $delegate->process($request);
    }
}
