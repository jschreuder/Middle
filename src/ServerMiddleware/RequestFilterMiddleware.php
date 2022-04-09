<?php declare(strict_types=1);

namespace jschreuder\Middle\ServerMiddleware;

use jschreuder\Middle\Controller\RequestFilterInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class RequestFilterMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $requestHandler): ResponseInterface
    {
        $controller = $request->getAttribute('controller');

        if ($controller instanceof RequestFilterInterface) {
            // Execute the request-filter from the controller
            $request = $controller->filterRequest($request);
        }

        // Filtered and validated (if applicable), let's continue on
        return $requestHandler->handle($request);
    }
}
