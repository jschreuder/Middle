<?php declare(strict_types = 1);

namespace jschreuder\Middle\Controller;

use Interop\Http\Middleware\DelegateInterface;
use Interop\Http\Middleware\ServerMiddlewareInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class FilterValidationMiddleware implements ServerMiddlewareInterface
{
    /** @var  callable */
    private $errorHandler;

    public function __construct(callable $errorHandler)
    {
        $this->errorHandler = $errorHandler;
    }

    public function process(ServerRequestInterface $request, DelegateInterface $delegate) : ResponseInterface
    {
        $controller = $request->getAttribute('controller');

        if ($controller instanceof RequestFilterInterface) {
            // Execute the request-filter from the controller
            $request = $controller->filterRequest($request);
        }

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
        return $delegate->next($request);
    }
}
