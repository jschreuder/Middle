<?php declare(strict_types = 1);

namespace jschreuder\Middle;

use Interop\Http\Middleware\DelegateInterface;
use Interop\Http\Middleware\ServerMiddlewareInterface;
use jschreuder\Middle\View\RendererInterface;
use jschreuder\Middle\View\ViewInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ControllerRunner implements ServerMiddlewareInterface
{
    /** @var  ?RendererInterface */
    private $renderer;

    public function __construct(RendererInterface $renderer = null)
    {
        $this->renderer = $renderer;
    }

    public function process(ServerRequestInterface $request, DelegateInterface $delegate) : ResponseInterface
    {
        $controller = $request->getAttribute('controller');
        $response = $controller($request);
        if ($this->renderer && $response instanceof ViewInterface) {
            $response = $this->renderer->render($request, $response);
        }

        return $response;
    }
}
