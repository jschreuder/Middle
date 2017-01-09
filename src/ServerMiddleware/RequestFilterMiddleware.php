<?php declare(strict_types = 1);

namespace jschreuder\Middle\ServerMiddleware;

use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use jschreuder\Middle\Controller\RequestFilterInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class RequestFilterMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, DelegateInterface $delegate): ResponseInterface
    {
        $controller = $request->getAttribute('controller');

        if ($controller instanceof RequestFilterInterface) {
            // Execute the request-filter from the controller
            $request = $controller->filterRequest($request);
        }

        // Filtered and validated (if applicable), let's continue on
        return $delegate->process($request);
    }
}
