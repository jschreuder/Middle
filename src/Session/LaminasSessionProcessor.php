<?php declare(strict_types=1);

namespace jschreuder\Middle\Session;

use Laminas\Session\Config\ConfigInterface;
use Laminas\Session\Container;
use Laminas\Session\SessionManager;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class LaminasSessionProcessor implements SessionProcessorInterface
{
    public function __construct(
        private readonly ?ConfigInterface $laminasSessionConfig = null
    )
    {
    }

    #[\Override]
    public function processRequest(ServerRequestInterface $request): ServerRequestInterface
    {
        $sessionManager = new SessionManager($this->laminasSessionConfig);
        $container = new Container(str_replace('.', '_', $request->getUri()->getHost()), $sessionManager);

        $session = new LaminasSession($sessionManager, $container);
        return $request->withAttribute('session', $session);
    }

    #[\Override]
    public function processResponse(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $response;
    }
}
