<?php declare(strict_types = 1);

namespace jschreuder\Middle\Session;

use jschreuder\Middle\HttpMiddlewareInterface;
use jschreuder\Middle\DelegateInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Session\Config\StandardConfig;
use Zend\Session\Container;
use Zend\Session\SessionManager;

class LoadZendSessionMiddleware implements HttpMiddlewareInterface
{
    /** @var  int */
    private $cookieLifetime;

    public function __construct(int $cookieLifetime)
    {
        $this->cookieLifetime = $cookieLifetime;
    }

    public function process(ServerRequestInterface $request, DelegateInterface $delegate) : ResponseInterface
    {
        $config = (new StandardConfig())
            ->setCookieLifetime($this->cookieLifetime)
            ->setCookieSecure(true)
            ->setCookieHttpOnly(true);
        $sessionManager = new SessionManager($config);
        $container = new Container(str_replace('.', '_', $request->getUri()->getHost()), $sessionManager);

        $session = new ZendSession($sessionManager, $container);
        return $delegate->next($request->withAttribute('session', $session));
    }

}
