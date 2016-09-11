<?php declare(strict_types = 1);

namespace jschreuder\Middle;

use jschreuder\Middle\View\RendererInterface;
use jschreuder\Middle\View\ViewInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ControllerRunner implements ApplicationInterface
{
    /** @var  RendererInterface */
    private $renderer;

    public function __construct(RendererInterface $renderer)
    {
        $this->renderer = $renderer;
    }

    public function execute(ServerRequestInterface $request) : ResponseInterface
    {
        $controller = $request->getAttribute('controller');
        if (is_string($controller) || !is_callable($controller)) {
            throw new \RuntimeException('Controller must be callable object or closure');
        }

        // Generate & render response
        $response = $controller($request);
        if ($response instanceof ViewInterface) {
            $response = $this->renderer->render($request, $response);
        }

        return $response;
    }
}
