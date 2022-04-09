<?php declare(strict_types=1);

namespace jschreuder\Middle\View;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Twig\Environment;

final class TwigRenderer implements RendererInterface
{
    private Environment $twig;
    private ResponseFactoryInterface $responseFactory;

    public function __construct(Environment $twig, ResponseFactoryInterface $responseFactory)
    {
        $this->twig = $twig;
        $this->responseFactory = $responseFactory;
    }

    public function render(ServerRequestInterface $request, ViewInterface $view): ResponseInterface
    {
        // Get new Response with HTML content type
        $response = $this->responseFactory->createResponse()->withHeader('Content-Type', 'text/html; charset=utf-8');

        // Write rendered Twig to body
        $response->getBody()->rewind();
        $response->getBody()->write($this->twig->render($view->getTemplate(), $view->getParameters()));
        $response->getBody()->rewind();

        return $response;
    }
}
