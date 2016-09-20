<?php declare(strict_types = 1);

namespace jschreuder\Middle\Controller;

use Interop\Http\Middleware\DelegateInterface;
use Interop\Http\Middleware\ServerMiddlewareInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ControllerRunner implements ServerMiddlewareInterface
{
    public function process(ServerRequestInterface $request, DelegateInterface $delegate) : ResponseInterface
    {
        $controller = $request->getAttribute('controller');
        if (!$controller instanceof ControllerInterface) {
            throw new \RuntimeException('Invalid controller type: ' . get_class($controller));
        }

        return $controller->execute($request);
    }
}
