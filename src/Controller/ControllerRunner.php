<?php declare(strict_types=1);

namespace jschreuder\Middle\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class ControllerRunner implements MiddlewareInterface
{
    #[\Override]
    public function process(ServerRequestInterface $request, RequestHandlerInterface $requestHandler): ResponseInterface
    {
        $controller = $request->getAttribute('controller');
        if (!$controller instanceof ControllerInterface) {
            throw new \RuntimeException('Invalid controller type: ' . get_debug_type($controller));
        }

        return $controller->execute($request);
    }
}
