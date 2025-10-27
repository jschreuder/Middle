<?php declare(strict_types=1);

namespace jschreuder\Middle;

use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

interface ApplicationStackInterface
{
    public function withLogger(LoggerInterface $logger): ApplicationStackInterface;

    public function withMiddleware(MiddlewareInterface $middleware): self;

    public function withoutMiddleware(MiddlewareInterface $middleware): self;

    public function process(ServerRequestInterface $request): ResponseInterface;
}
