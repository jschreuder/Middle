<?php

namespace jschreuder\Middle;

use Interop\Http\Middleware\ServerMiddlewareInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

interface ApplicationStackInterface
{
    public function withMiddleware(ServerMiddlewareInterface $middleware) : ApplicationStack;

    public function withoutMiddleware(ServerMiddlewareInterface $middleware) : ApplicationStack;

    public function process(ServerRequestInterface $request) : ResponseInterface;
}