<?php declare(strict_types = 1);

namespace jschreuder\Middle;

use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

interface ApplicationStackInterface
{
    public function withMiddleware(MiddlewareInterface $middleware): ApplicationStack;

    public function withoutMiddleware(MiddlewareInterface $middleware): ApplicationStack;

    public function process(ServerRequestInterface $request): ResponseInterface;
}
