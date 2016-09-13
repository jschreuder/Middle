<?php

namespace jschreuder\Middle\Session;

use Lcobucci\JWT\Token;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

interface JwtToPsrMapperInterface
{
    const ISSUED_AT_CLAIM = 'iat';
    const SESSION_CLAIM   = 'session-data';
    const DEFAULT_COOKIE  = 'slsession';

    /** @return  ?Token */
    public function parseToken(ServerRequestInterface $request);

    public function appendToken(
        SessionInterface $session,
        ResponseInterface $response,
        Token $token = null
    ) : ResponseInterface;

    public function extractSessionContainer(Token $token = null) : SessionInterface;
}