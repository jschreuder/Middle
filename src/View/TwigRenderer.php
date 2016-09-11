<?php declare(strict_types = 1);

namespace jschreuder\Middle\Application\View;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class TwigRenderer implements RendererInterface
{
    use CreateResponseTrait;

    /** @var  \Twig_Environment */
    private $twig;

    public function __construct(\Twig_Environment $twig)
    {
        $this->twig = $twig;
    }

    public function render(ServerRequestInterface $request, ViewInterface $view) : ResponseInterface
    {
        $response = $this->createResponse($view);
        $response->getBody()->write($this->twig->render($view->getTemplate(), $view->getParameters()));
        return $response;
    }
}
