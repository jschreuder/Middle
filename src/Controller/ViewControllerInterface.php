<?php declare(strict_types = 1);

namespace jschreuder\Middle\Controller;

use jschreuder\Middle\View\ViewInterface;
use Psr\Http\Message\ServerRequestInterface;

interface ViewControllerInterface
{
    public function __invoke(ServerRequestInterface $request) : ViewInterface;
}
