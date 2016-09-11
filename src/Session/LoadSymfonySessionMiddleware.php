<?php declare(strict_types = 1);

namespace jschreuder\Middle\Session;

use jschreuder\Middle\ApplicationInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Session\Config\StandardConfig;
use Zend\Session\SessionManager;

class LoadSymfonySessionMiddleware implements ApplicationInterface
{
    /** @var  ApplicationInterface */
    private $application;

    /** @var  int */
    private $cookieLifetime;

    public function __construct(ApplicationInterface $application, int $cookieLifetime)
    {
        $this->application = $application;
        $this->cookieLifetime = $cookieLifetime;
    }

    public function execute(ServerRequestInterface $request) : ResponseInterface
    {
        $config = (new StandardConfig())
            ->setCookieLifetime($this->cookieLifetime)
            ->setCookieSecure(true)
            ->setCookieHttpOnly(true);
        $sessionManager = new SessionManager($config);

        $session = new ZendSession($sessionManager, $request->getUri()->getHost());
        return $this->application->execute($request->withAttribute('session', $session));
    }

}
