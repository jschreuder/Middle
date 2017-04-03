<?php declare(strict_types = 1);

namespace jschreuder\Middle\Session;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Session\Config\ConfigInterface;
use Zend\Session\Container;
use Zend\Session\SessionManager;

final class ZendSessionProcessor implements SessionProcessorInterface
{
    /** @var  ?ConfigInterface */
    private $zendSessionConfig;

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
