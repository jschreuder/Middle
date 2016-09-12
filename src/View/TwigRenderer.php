<?php declare(strict_types = 1);

namespace jschreuder\Middle\View;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response;

final class TwigRenderer implements RendererInterface
{
    /** @var  \Twig_Environment */
    private $twig;

    public function __construct(\Twig_Environment $twig)
    {
        $this->twig = $twig;
    }

    public function render(ServerRequestInterface $request, ViewInterface $view) : ResponseInterface
    {
        $response = (new Response())
            ->withHeader('Content-Type', 'text/html; charset=utf-8');
        $response->getBody()->write($this->twig->render($view->getTemplate(), $view->getParameters()));
        $response->getBody()->rewind();
        return $response;
    }
}
