<?php declare(strict_types = 1);

namespace jschreuder\Middle\Session;

use Interop\Http\Middleware\DelegateInterface;
use Interop\Http\Middleware\ServerMiddlewareInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class JwtLoadSessionMiddleware implements ServerMiddlewareInterface
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
