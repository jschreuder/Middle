<?php declare(strict_types = 1);

namespace jschreuder\Middle\Session;

use Laminas\Session\Config\ConfigInterface;
use Laminas\Session\Container;
use Laminas\Session\SessionManager;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ZendSessionProcessor implements SessionProcessorInterface
{
    private ?ConfigInterface $zendSessionConfig;

    public function __construct(?ConfigInterface $zendSessionConfig = null)
    {
        $this->zendSessionConfig = $zendSessionConfig;
    }

    public function processRequest(ServerRequestInterface $request): ServerRequestInterface
    {
        $sessionManager = new SessionManager($this->zendSessionConfig);
        $container = new Container(str_replace('.', '_', $request->getUri()->getHost()), $sessionManager);

        $session = new ZendSession($sessionManager, $container);
        return $request->withAttribute('session', $session);
    }

    public function processResponse(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $response;
    }
}
