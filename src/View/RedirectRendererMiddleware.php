<?php declare(strict_types = 1);

namespace jschreuder\Middle\View;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class RedirectRendererMiddleware implements RendererInterface
{
    /** @var  RendererInterface */
    private $renderer;

    /** @var  ResponseInterface */
    private $baseResponse;

    public function __construct(RendererInterface $renderer, ResponseInterface $baseResponse)
    {
        $this->renderer = $renderer;
        $this->baseResponse = $baseResponse;
    }

    public function render(ServerRequestInterface $request, ViewInterface $view) : ResponseInterface
    {
        if ($view->getStatusCode() < 300 || $view->getStatusCode() >= 400) {
            return $this->renderer->render($request, $view);
        }

        $headers = $view->getHeaders();
        if (!isset($headers['Location'])) {
            throw new \UnderflowException('Location header must be set on View');
        }

        $response = $this->baseResponse;
        foreach ($headers as $name => $value) {
            $response = $response->withHeader($name, $value);
        }
        return $response->withStatus($view->getStatusCode());
    }
}
