<?php declare(strict_types = 1);

namespace jschreuder\Middle\ServerMiddleware;

use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use jschreuder\Middle\Session\SessionProcessorInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class SessionMiddleware implements MiddlewareInterface
{
    /** @var  SessionProcessorInterface */
    private $sessionProcessor;

    public function __construct(SessionProcessorInterface $sessionProcessor)
    {
        $this->sessionProcessor = $sessionProcessor;
    }

    public function process(ServerRequestInterface $request, DelegateInterface $delegate): ResponseInterface
    {
        $request = $this->sessionProcessor->processRequest($request);
        $response = $delegate->process($request);
        return $this->sessionProcessor->processResponse($request, $response);
    }
}
