<?php declare(strict_types=1);

namespace jschreuder\Middle\View;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

interface RendererInterface
{
    public function render(ServerRequestInterface $request, ViewInterface $view): ResponseInterface;
}