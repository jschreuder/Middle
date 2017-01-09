<?php declare(strict_types = 1);

namespace jschreuder\Middle\ServerMiddleware;

use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use jschreuder\Middle\Session\ZendSession;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Session\Config\StandardConfig;
use Zend\Session\Container;
use Zend\Session\SessionManager;

final class ZendLoadSessionMiddleware implements MiddlewareInterface
{
    /** @var  int */
    private $cookieLifetime;

    public function __construct(int $cookieLifetime)
    {
        $this->cookieLifetime = $cookieLifetime;
    }

    public function process(ServerRequestInterface $request, DelegateInterface $delegate): ResponseInterface
    {
        $config = (new StandardConfig())
            ->setCookieLifetime($this->cookieLifetime)
            ->setCookieSecure(true)
            ->setCookieHttpOnly(true);
        $sessionManager = new SessionManager($config);
        $container = new Container(str_replace('.', '_', $request->getUri()->getHost()), $sessionManager);

        $session = new ZendSession($sessionManager, $container);
        return $delegate->process($request->withAttribute('session', $session));
    }

}
