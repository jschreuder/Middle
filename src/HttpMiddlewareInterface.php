<?php declare(strict_types = 1);

namespace jschreuder\Middle;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/** Copy-pasted from PSR-15 proposed ServerMiddlewareInterface */
interface HttpMiddlewareInterface
{
    public function process(ServerRequestInterface $request, DelegateInterface $delegate) : ResponseInterface;
}
