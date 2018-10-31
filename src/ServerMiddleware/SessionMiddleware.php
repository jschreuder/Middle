<?php declare(strict_types = 1);

namespace jschreuder\Middle\ServerMiddleware;

use jschreuder\Middle\Session\SessionProcessorInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class SessionMiddleware implements MiddlewareInterface
{
    /** @var  SessionProcessorInterface */
    private $sessionProcessor;

    public function __construct(SessionProcessorInterface $sessionProcessor)
    {
        $this->sessionProcessor = $sessionProcessor;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $requestHandler): ResponseInterface
    {
        $request = $this->sessionProcessor->processRequest($request);
        $response = $requestHandler->handle($request);
        return $this->sessionProcessor->processResponse($request, $response);
    }
}
