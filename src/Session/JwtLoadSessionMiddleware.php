<?php declare(strict_types = 1);

namespace jschreuder\Middle\Session;

use jschreuder\Middle\DelegateInterface;
use jschreuder\Middle\HttpMiddlewareInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class JwtLoadSessionMiddleware implements HttpMiddlewareInterface
{
    /** @var  JwtToPsrMapperInterface */
    private $mapper;

    /**
     * JwtLoadSessionMiddleware constructor.
     * @param JwtToPsrMapper $mapper
     */
    public function __construct(JwtToPsrMapperInterface $mapper)
    {
        $this->mapper = $mapper;
    }

    public function process(ServerRequestInterface $request, DelegateInterface $delegate) : ResponseInterface
    {
        $token = $this->mapper->parseToken($request);
        $sessionContainer = $this->mapper->extractSessionContainer($token);

        $response = $delegate->next($request->withAttribute('session', $sessionContainer));

        return $this->mapper->appendToken($sessionContainer, $response, $token);
    }
}
