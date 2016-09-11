<?php declare(strict_types = 1);

namespace jschreuder\Middle\Application\View;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\RedirectResponse;

class RedirectRendererMiddleware implements RendererInterface
{
    /** @var  RendererInterface */
    private $renderer;

    public function __construct(RendererInterface $renderer)
    {
        $this->renderer = $renderer;
    }

    public function render(ServerRequestInterface $request, ViewInterface $view) : ResponseInterface
    {
        if ($view->getStatusCode() < 300 || $view->getStatusCode() >= 400) {
            return $this->renderer->render($request, $view);
        }

        $headers = $view->getHeaders();
        return new RedirectResponse($headers['Location'], $view->getStatusCode(), $headers);
    }
}
