<?php declare(strict_types = 1);

namespace jschreuder\Middle;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/** Copy-pasted from PSR-15 proposed DelegateInterface */
interface DelegateInterface
{
    public function next(ServerRequestInterface $request) : ResponseInterface;
}
