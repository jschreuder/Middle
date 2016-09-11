<?php declare(strict_types = 1);

namespace jschreuder\Middle\Application;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

interface ApplicationInterface
{
    public function execute(ServerRequestInterface $request) : ResponseInterface;
}