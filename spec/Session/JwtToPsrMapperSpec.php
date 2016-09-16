<?php

namespace spec\jschreuder\Middle\Session;

use Dflydev\FigCookies\SetCookie;
use jschreuder\Middle\Session\JwtToPsrMapper;
use jschreuder\Middle\Session\JwtToPsrMapperInterface;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Signer;
use Lcobucci\JWT\Token;
use Lcobucci\JWT\ValidationData;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Psr\Http\Message\ServerRequestInterface;

/** @mixin  JwtToPsrMapper */
class JwtToPsrMapperSpec extends ObjectBehavior
{
    const PRIVATE_KEY = '-----BEGIN RSA PRIVATE KEY-----
MIIBOgIBAAJBAJZ37zflIWLaeFfzBcQLPVcwB9dTQKzJB+BkzAUS+w9a4R5XZIJr
/iOKU3znyDz91yoojDU0UcmOu3Ah7uX7Co0CAwEAAQJAZVJfyLDHWYypyvd/43J6
HNLgBNQv0eoRHr5hT+1nF//etGxkLb+Ih26AenxCyMiA9UiRv+pJvrLSiiK5cGka
IQIhAM7l7LVatmEWnWJxydfDpPa19HISlVHxic8aH0DNP6+5AiEAui2h3WG7V9oE
Po4mQZxw5lxIhuNNzlbEDldWXFT9E3UCIQCRL42E0cwrozf8Dgdq7nKDYbnQlrPL
1egzuYv26FDpmQIgULRhOy8XX+DBAEDscnqXMjSEt/wmiTBxcmoHpKSuw9UCIGTY
UJmjVQ6FwKlTMzvayj3oKaTwsJNGb82SiTxAJvkn
-----END RSA PRIVATE KEY-----';

    const PUBLIC_KEY = '-----BEGIN PUBLIC KEY-----
MFwwDQYJKoZIhvcNAQEBBQADSwAwSAJBAJZ37zflIWLaeFfzBcQLPVcwB9dTQKzJ
B+BkzAUS+w9a4R5XZIJr/iOKU3znyDz91yoojDU0UcmOu3Ah7uX7Co0CAwEAAQ==
-----END PUBLIC KEY-----';

    /** @var  Signer */
    private $signer;

    /** @var  SetCookie */
    private $defaultCookie;

    /** @var  Parser */
    private $tokenParser;

    public function let(Signer $signer, SetCookie $defaultCookie, Parser $tokenParser)
    {
        $this->signer = $signer;
        $this->defaultCookie = $defaultCookie;
        $this->tokenParser = $tokenParser;
        $this->beConstructedWith($signer, self::PRIVATE_KEY, self::PUBLIC_KEY, $defaultCookie, $tokenParser, 3600, 60);
    }

    public function it_is_initializable()
    {
        $this->beConstructedThrough('fromAsymmetricKeyDefaults', [
            self::PRIVATE_KEY, self::PUBLIC_KEY, 3600
        ]);
        $this->shouldHaveType(JwtToPsrMapper::class);
    }

    public function it_can_parse_token_from_request(ServerRequestInterface $request, Token $token)
    {
        $cookieName = 'cookie.name';
        $cookieValue = 'cookie.value';
        $request->getCookieParams()->willReturn([$cookieName => $cookieValue]);
        $this->defaultCookie->getName()->willReturn($cookieName);
        $this->tokenParser->parse($cookieValue)->willReturn($token);
        $token->validate(new Argument\Token\TypeToken(ValidationData::class))->willReturn(true);
        $this->parseToken($request)->shouldReturn($token);
    }

    public function it_returns_null_if_no_token_in_request(ServerRequestInterface $request)
    {
        $cookieName = 'cookie.name';
        $request->getCookieParams()->willReturn([]);
        $this->defaultCookie->getName()->willReturn($cookieName);
        $this->parseToken($request)->shouldReturn(null);
    }

    public function it_returns_null_parsing_token_fails(ServerRequestInterface $request)
    {
        $cookieName = 'cookie.name';
        $cookieValue = 'cookie.value';
        $request->getCookieParams()->willReturn([$cookieName => $cookieValue]);
        $this->defaultCookie->getName()->willReturn($cookieName);
        $this->tokenParser->parse($cookieValue)->willThrow(new \InvalidArgumentException());
        $this->parseToken($request)->shouldReturn(null);
    }

    public function it_returns_null_validating_token_fails(ServerRequestInterface $request, Token $token)
    {
        $cookieName = 'cookie.name';
        $cookieValue = 'cookie.value';
        $request->getCookieParams()->willReturn([$cookieName => $cookieValue]);
        $this->defaultCookie->getName()->willReturn($cookieName);
        $this->tokenParser->parse($cookieValue)->willReturn($token);
        $token->validate(new Argument\Token\TypeToken(ValidationData::class))->willReturn(false);
        $this->parseToken($request)->shouldReturn(null);
    }

    public function it_can_extract_session_from_token(Token $token)
    {
        $sessionArray = ['test' => 'rest'];
        $token->verify($this->signer, self::PUBLIC_KEY)->willReturn(true);
        $token->getClaim(JwtToPsrMapperInterface::SESSION_CLAIM, [])->willReturn($sessionArray);
        $session = $this->extractSessionContainer($token);
        $session->get('test')->shouldReturn($sessionArray['test']);
    }

    public function it_creates_empty_session_without_token()
    {
        $session = $this->extractSessionContainer(null);
        $session->isEmpty()->shouldReturn(true);
    }

    public function it_returns_empty_session_when_session_doesnt_verify(Token $token)
    {
        $token->verify($this->signer, self::PUBLIC_KEY)->willReturn(false);
        $session = $this->extractSessionContainer($token);
        $session->isEmpty()->shouldReturn(true);
    }

    public function it_returns_empty_session_when_session_doesnt_have_signature(Token $token)
    {
        $token->verify($this->signer, self::PUBLIC_KEY)->willThrow(\BadMethodCallException::class);
        $session = $this->extractSessionContainer($token);
        $session->isEmpty()->shouldReturn(true);
    }
}
