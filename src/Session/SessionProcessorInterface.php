<?php declare(strict_types=1);

namespace jschreuder\Middle\Session;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

interface SessionProcessorInterface
{
    public function processRequest(
        ServerRequestInterface $request,
    ): ServerRequestInterface;

    public function processResponse(
        ServerRequestInterface $request,
        ResponseInterface $response,
    ): ResponseInterface;
}
