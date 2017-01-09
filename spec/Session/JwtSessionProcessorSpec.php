<?php

namespace spec\jschreuder\Middle\Session;

use Dflydev\FigCookies\SetCookie;
use Dflydev\FigCookies\SetCookies;
use jschreuder\Middle\Session\JwtSessionProcessor;
use jschreuder\Middle\Session\SessionInterface;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Signer;
use Lcobucci\JWT\Token;
use Lcobucci\JWT\ValidationData;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class JwtSessionProcessorSpec extends ObjectBehavior
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
        $this->shouldHaveType(JwtSessionProcessor::class);
    }

    public function it_can_process_a_request(
        Token $token,
        ServerRequestInterface $request1,
        ServerRequestInterface $request2,
        ServerRequestInterface $request3
    )
    {
        $cookieName = 'cookie.name';
        $cookieValue = 'cookie.value';
        $sessionArray = ['test' => 'rest'];
        $request1->getCookieParams()->willReturn([$cookieName => $cookieValue]);
        $this->defaultCookie->getName()->willReturn($cookieName);
        $this->tokenParser->parse($cookieValue)->willReturn($token);
        $token->validate(new Argument\Token\TypeToken(ValidationData::class))->willReturn(true);
        $token->verify($this->signer, self::PUBLIC_KEY)->willReturn(true);
        $token->getClaim(JwtSessionProcessor::SESSION_CLAIM, [])->willReturn($sessionArray);
        $this->parseToken($request1)->shouldReturn($token);

        $request1->withAttribute('session', new Argument\Token\TypeToken(SessionInterface::class))->willReturn($request2);
        $request2->withAttribute('session.token', $token)->willReturn($request3);
        $this->processRequest($request1)->shouldReturn($request3);
    }

    public function it_can_process_a_response(
        Token $token,
        SessionInterface $session,
        ServerRequestInterface $request,
        ResponseInterface $response
    )
    {
        $request->getAttribute('session')->willReturn($session);
        $request->getAttribute('session.token')->willReturn($token);

        $session->hasChanged()->willReturn(false);
        $session->isEmpty()->willReturn(false);

        $this->processResponse($request, $response)->shouldReturn($response);
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
        $token->getClaim(JwtSessionProcessor::SESSION_CLAIM, [])->willReturn($sessionArray);
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

    public function it_doesnt_append_unchanged_nonempty_nonrefresh_session(
        SessionInterface $session,
        ResponseInterface $response
    )
    {
        $session->hasChanged()->willReturn(false);
        $session->isEmpty()->willReturn(false);
        $token = null;

        $this->appendToken($session, $response, $token)->shouldReturn($response);
    }

    public function it_appends_unchanged_nonempty_refresh_session(
        SessionInterface $session,
        ResponseInterface $response1,
        ResponseInterface $response2,
        ResponseInterface $response3,
        SetCookie $cookie2,
        SetCookie $cookie3,
        Token $token
    )
    {
        $sessionArray = ['test' => 'rest'];
        $cookieString = 'test=rest';
        $session->hasChanged()->willReturn(false);
        $session->isEmpty()->willReturn(false);
        $session->toArray()->willReturn($sessionArray);
        $token->hasClaim(JwtSessionProcessor::ISSUED_AT_CLAIM)->willReturn(true);
        $token->getClaim(JwtSessionProcessor::ISSUED_AT_CLAIM)->willReturn(time() - 3605);

        $response1->getHeader(SetCookies::SET_COOKIE_HEADER)->willReturn([]);
        $response1->withoutHeader(SetCookies::SET_COOKIE_HEADER)->willReturn($response2);
        $response2->withAddedHeader(SetCookies::SET_COOKIE_HEADER, $cookieString)
            ->willReturn($response3);

        $this->defaultCookie->withValue(new Argument\Token\TypeToken(Token::class))->willReturn($cookie2);
        $cookie2->withExpires(new Argument\Token\TypeToken('int'))->willReturn($cookie3);
        $cookie3->getName()->willReturn(JwtSessionProcessor::DEFAULT_COOKIE);
        $cookie3->__toString()->willReturn($cookieString);

        $this->appendToken($session, $response1, $token)->shouldReturn($response3);
    }

    public function it_doesnt_append_unchanged_empty_nonrefresh_session(
        SessionInterface $session,
        ResponseInterface $response,
        Token $token
    )
    {
        $session->hasChanged()->willReturn(false);
        $session->isEmpty()->willReturn(true);
        $token->hasClaim(JwtSessionProcessor::ISSUED_AT_CLAIM)->willReturn(false);

        $this->appendToken($session, $response, $token)->shouldReturn($response);
    }

    public function it_doesnt_append_unchanged_empty_refresh_session(
        SessionInterface $session,
        ResponseInterface $response,
        Token $token
    )
    {
        $session->hasChanged()->willReturn(false);
        $session->isEmpty()->willReturn(true);
        $token->hasClaim(JwtSessionProcessor::ISSUED_AT_CLAIM)->willReturn(true);
        $token->getClaim(JwtSessionProcessor::ISSUED_AT_CLAIM)->willReturn(time() - 3605);

        $this->appendToken($session, $response, $token)->shouldReturn($response);
    }

    public function it_appends_changed_nonempty_nonrefresh_session(
        SessionInterface $session,
        ResponseInterface $response1,
        ResponseInterface $response2,
        ResponseInterface $response3,
        SetCookie $cookie2,
        SetCookie $cookie3,
        Token $token
    )
    {
        $sessionArray = ['test' => 'rest'];
        $cookieString = 'test=rest';
        $session->hasChanged()->willReturn(true);
        $session->isEmpty()->willReturn(false);
        $session->toArray()->willReturn($sessionArray);
        $token->hasClaim(JwtSessionProcessor::ISSUED_AT_CLAIM)->willReturn(true);
        $token->getClaim(JwtSessionProcessor::ISSUED_AT_CLAIM)->willReturn(time() - 5);

        $response1->getHeader(SetCookies::SET_COOKIE_HEADER)->willReturn([]);
        $response1->withoutHeader(SetCookies::SET_COOKIE_HEADER)->willReturn($response2);
        $response2->withAddedHeader(SetCookies::SET_COOKIE_HEADER, $cookieString)
            ->willReturn($response3);

        $this->defaultCookie->withValue(new Argument\Token\TypeToken(Token::class))->willReturn($cookie2);
        $cookie2->withExpires(new Argument\Token\TypeToken('int'))->willReturn($cookie3);
        $cookie3->getName()->willReturn(JwtSessionProcessor::DEFAULT_COOKIE);
        $cookie3->__toString()->willReturn($cookieString);

        $this->appendToken($session, $response1, $token)->shouldReturn($response3);
    }

    public function it_appends_changed_nonempty_refresh_session(
        SessionInterface $session,
        ResponseInterface $response1,
        ResponseInterface $response2,
        ResponseInterface $response3,
        SetCookie $cookie2,
        SetCookie $cookie3,
        Token $token
    )
    {
        $sessionArray = ['test' => 'rest'];
        $cookieString = 'test=rest';
        $session->hasChanged()->willReturn(true);
        $session->isEmpty()->willReturn(false);
        $session->toArray()->willReturn($sessionArray);
        $token->hasClaim(JwtSessionProcessor::ISSUED_AT_CLAIM)->willReturn(true);
        $token->getClaim(JwtSessionProcessor::ISSUED_AT_CLAIM)->willReturn(time() - 3605);

        $response1->getHeader(SetCookies::SET_COOKIE_HEADER)->willReturn([]);
        $response1->withoutHeader(SetCookies::SET_COOKIE_HEADER)->willReturn($response2);
        $response2->withAddedHeader(SetCookies::SET_COOKIE_HEADER, $cookieString)
            ->willReturn($response3);

        $this->defaultCookie->withValue(new Argument\Token\TypeToken(Token::class))->willReturn($cookie2);
        $cookie2->withExpires(new Argument\Token\TypeToken('int'))->willReturn($cookie3);
        $cookie3->getName()->willReturn(JwtSessionProcessor::DEFAULT_COOKIE);
        $cookie3->__toString()->willReturn($cookieString);

        $this->appendToken($session, $response1, $token)->shouldReturn($response3);
    }

    public function it_appends_changed_empty_nonrefresh_session(
        SessionInterface $session,
        ResponseInterface $response1,
        ResponseInterface $response2,
        ResponseInterface $response3,
        SetCookie $cookie2,
        SetCookie $cookie3,
        Token $token
    )
    {
        $sessionArray = ['test' => 'rest'];
        $cookieString = '';
        $session->hasChanged()->willReturn(true);
        $session->isEmpty()->willReturn(true);
        $session->toArray()->willReturn($sessionArray);
        $token->hasClaim(JwtSessionProcessor::ISSUED_AT_CLAIM)->willReturn(true);
        $token->getClaim(JwtSessionProcessor::ISSUED_AT_CLAIM)->willReturn(time() - 5);

        $response1->getHeader(SetCookies::SET_COOKIE_HEADER)->willReturn([]);
        $response1->withoutHeader(SetCookies::SET_COOKIE_HEADER)->willReturn($response2);
        $response2->withAddedHeader(SetCookies::SET_COOKIE_HEADER, $cookieString)
            ->willReturn($response3);

        $this->defaultCookie->withValue(null)->willReturn($cookie2);
        $cookie2->withExpires(new Argument\Token\TypeToken('int'))->willReturn($cookie3);
        $cookie3->getName()->willReturn(JwtSessionProcessor::DEFAULT_COOKIE);
        $cookie3->__toString()->willReturn($cookieString);

        $this->appendToken($session, $response1, $token)->shouldReturn($response3);
    }

    public function it_appends_changed_empty_refresh_session(
        SessionInterface $session,
        ResponseInterface $response1,
        ResponseInterface $response2,
        ResponseInterface $response3,
        SetCookie $cookie2,
        SetCookie $cookie3,
        Token $token
    )
    {
        $sessionArray = ['test' => 'rest'];
        $cookieString = '';
        $session->hasChanged()->willReturn(true);
        $session->isEmpty()->willReturn(true);
        $session->toArray()->willReturn($sessionArray);
        $token->hasClaim(JwtSessionProcessor::ISSUED_AT_CLAIM)->willReturn(true);
        $token->getClaim(JwtSessionProcessor::ISSUED_AT_CLAIM)->willReturn(time() - 3605);

        $response1->getHeader(SetCookies::SET_COOKIE_HEADER)->willReturn([]);
        $response1->withoutHeader(SetCookies::SET_COOKIE_HEADER)->willReturn($response2);
        $response2->withAddedHeader(SetCookies::SET_COOKIE_HEADER, $cookieString)
            ->willReturn($response3);

        $this->defaultCookie->withValue(null)->willReturn($cookie2);
        $cookie2->withExpires(new Argument\Token\TypeToken('int'))->willReturn($cookie3);
        $cookie3->getName()->willReturn(JwtSessionProcessor::DEFAULT_COOKIE);
        $cookie3->__toString()->willReturn($cookieString);

        $this->appendToken($session, $response1, $token)->shouldReturn($response3);
    }
}
