<?php

namespace jschreuder\Middle;

use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

interface ApplicationStackInterface
{
    public function withMiddleware(MiddlewareInterface $middleware) : ApplicationStack;

    public function withoutMiddleware(MiddlewareInterface $middleware) : ApplicationStack;

    public function process(ServerRequestInterface $request) : ResponseInterface;
}
