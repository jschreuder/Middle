<?php declare(strict_types = 1);

namespace jschreuder\Middle\Session;

use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class JwtLoadSessionMiddleware implements MiddlewareInterface
{
    /** @var  JwtToPsrMapperInterface */
    private $mapper;

    public function __construct(JwtToPsrMapperInterface $mapper)
    {
        $this->mapper = $mapper;
    }

    public function process(ServerRequestInterface $request, DelegateInterface $delegate) : ResponseInterface
    {
        $token = $this->mapper->parseToken($request);
        $sessionContainer = $this->mapper->extractSessionContainer($token);

        $response = $delegate->process($request->withAttribute('session', $sessionContainer));

        return $this->mapper->appendToken($sessionContainer, $response, $token);
    }
}
