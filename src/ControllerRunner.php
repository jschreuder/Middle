<?php declare(strict_types = 1);

namespace jschreuder\Middle;

use jschreuder\Middle\View\RendererInterface;
use jschreuder\Middle\View\ViewInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ControllerRunner implements ApplicationInterface
{
    /** @var  ?RendererInterface */
    private $renderer;

    public function __construct(RendererInterface $renderer = null)
    {
        $this->renderer = $renderer;
    }

    public function execute(ServerRequestInterface $request) : ResponseInterface
    {
        $controller = $request->getAttribute('controller');
        $response = $controller($request);
        if ($this->renderer && $response instanceof ViewInterface) {
            $response = $this->renderer->render($request, $response);
        }

        return $response;
    }
}
