<?php declare(strict_types=1);

namespace jschreuder\Middle\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

interface ControllerInterface
{
    public function execute(ServerRequestInterface $request): ResponseInterface;
}
