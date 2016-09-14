<?php declare(strict_types = 1);

namespace jschreuder\Middle\View;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class TwigRenderer implements RendererInterface
{
    /** @var  \Twig_Environment */
    private $twig;

    /** @var  ResponseInterface */
    private $baseResponse;

    public function __construct(\Twig_Environment $twig, ResponseInterface $baseResponse)
    {
        $this->twig = $twig;
        $this->baseResponse = $baseResponse;
    }

    public function render(ServerRequestInterface $request, ViewInterface $view) : ResponseInterface
    {
        // Get new Response with HTML content type
        $response = $this->baseResponse->withHeader('Content-Type', 'text/html; charset=utf-8');

        // Write rendered Twig to body
        $response->getBody()->rewind();
        $response->getBody()->write($this->twig->render($view->getTemplate(), $view->getParameters()));
        $response->getBody()->rewind();

        return $response;
    }
}
