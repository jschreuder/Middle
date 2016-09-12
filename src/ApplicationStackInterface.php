<?php

namespace jschreuder\Middle;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/** Copy-pasted from PSR-15 proposed StackInterface */
interface ApplicationStackInterface
{
    public function withMiddleware(HttpMiddlewareInterface $middleware) : ApplicationStack;

    public function withoutMiddleware(HttpMiddlewareInterface $middleware) : ApplicationStack;

    public function process(ServerRequestInterface $request) : ResponseInterface;
}