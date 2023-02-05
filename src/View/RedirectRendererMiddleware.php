<?php declare(strict_types=1);

namespace jschreuder\Middle\View;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class RedirectRendererMiddleware implements RendererInterface
{
    public function __construct(
        private RendererInterface $renderer,
        private ResponseFactoryInterface $responseFactory
    )
    {
    }

    public function render(ServerRequestInterface $request, ViewInterface $view): ResponseInterface
    {
        if ($view->getStatusCode() < 300 || $view->getStatusCode() >= 400) {
            return $this->renderer->render($request, $view);
        }

        $headers = $view->getHeaders();
        if (!isset($headers['Location'])) {
            throw new \UnderflowException('Location header must be set on View');
        }

        $response = $this->responseFactory->createResponse($view->getStatusCode());
        foreach ($headers as $name => $value) {
            $response = $response->withHeader($name, $value);
        }
        return $response;
    }
}
