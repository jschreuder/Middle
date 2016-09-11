<?php declare(strict_types = 1);

namespace jschreuder\Middle\Application\View;

use Zend\Diactoros\Response\HtmlResponse;
use Zend\Diactoros\Response\JsonResponse;

trait CreateResponseTrait
{
    private function createResponse(ViewInterface $view)
    {
        if ($view->getContentType() === ViewInterface::CONTENT_TYPE_HTML) {
            return new HtmlResponse('', $view->getStatusCode());
        } elseif ($view->getContentType() === ViewInterface::CONTENT_TYPE_JSON) {
            return new JsonResponse([], $view->getStatusCode());
        } else {
            throw new \RuntimeException('Unsupported content type: ' . $view->getStatusCode());
        }
    }
}