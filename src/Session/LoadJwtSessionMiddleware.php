<?php declare(strict_types = 1);

namespace jschreuder\Middle\Session;

use Dflydev\FigCookies\FigResponseCookies;
use Dflydev\FigCookies\SetCookie;
use jschreuder\Middle\DelegateInterface;
use jschreuder\Middle\HttpMiddlewareInterface;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Signer;
use Lcobucci\JWT\Token;
use Lcobucci\JWT\ValidationData;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * A copy-paste from ocramius/psr7-session, but without needing to pull in
 * Stratigility and working with our session container.
 */
final class LoadJwtSessionMiddleware implements HttpMiddlewareInterface
{
    const ISSUED_AT_CLAIM      = 'iat';
    const SESSION_CLAIM        = 'session-data';
    const DEFAULT_COOKIE       = 'slsession';
    const DEFAULT_REFRESH_TIME = 60;

    /** @var  Signer */
    private $signer;

    /** @var  string */
    private $signatureKey;

    /** @var  string */
    private $verificationKey;

    /** @var  int */
    private $expirationTime;

    /** @var  int */
    private $refreshTime;

    /** @var  Parser */
    private $tokenParser;

    /** @var  SetCookie */
    private $defaultCookie;

    private function __construct(
        Signer $signer,
        string $signatureKey,
        string $verificationKey,
        SetCookie $defaultCookie,
        Parser $tokenParser,
        int $expirationTime,
        int $refreshTime = self::DEFAULT_REFRESH_TIME
    ) {
        $this->signer              = $signer;
        $this->signatureKey        = $signatureKey;
        $this->verificationKey     = $verificationKey;
        $this->tokenParser         = $tokenParser;
        $this->defaultCookie       = clone $defaultCookie;
        $this->expirationTime      = $expirationTime;
        $this->refreshTime         = $refreshTime;
    }

    public static function fromAsymmetricKeyDefaults(
        string $privateRsaKey,
        string $publicRsaKey,
        int $expirationTime
    ) : LoadJwtSessionMiddleware {
        return new self(
            new Signer\Rsa\Sha256(),
            $privateRsaKey,
            $publicRsaKey,
            SetCookie::create(self::DEFAULT_COOKIE)
                ->withSecure(true)
                ->withHttpOnly(true)
                ->withPath('/'),
            new Parser(),
            $expirationTime
        );
    }

    public function process(ServerRequestInterface $request, DelegateInterface $delegate) : ResponseInterface
    {
        $token = $this->parseToken($request);
        $sessionContainer = $this->extractSessionContainer($token);

        $response = $delegate->next($request->withAttribute('session', $sessionContainer));

        return $this->appendToken($sessionContainer, $response, $token);
    }

    /** @return  ?Token */
    private function parseToken(ServerRequestInterface $request)
    {
        $cookies    = $request->getCookieParams();
        $cookieName = $this->defaultCookie->getName();

        if (!isset($cookies[$cookieName])) {
            return null;
        }

        try {
            $token = $this->tokenParser->parse($cookies[$cookieName]);
        } catch (\InvalidArgumentException $invalidToken) {
            return null;
        }

        if (!$token->validate(new ValidationData())) {
            return null;
        }

        return $token;
    }

    private function extractSessionContainer(Token $token = null) : SessionInterface
    {
        try {
            if (is_null($token) || !$token->verify($this->signer, $this->verificationKey)) {
                return new Session();
            }

            // Re-encode the payload and decode as array to not get stdClass tree
            return new Session(
                json_decode(json_encode($token->getClaim(self::SESSION_CLAIM, [])), true)
            );
        } catch (\BadMethodCallException $invalidToken) {
            return new Session();
        }
    }

    private function appendToken(
        SessionInterface $sessionContainer,
        ResponseInterface $response,
        Token $token = null
    ) : ResponseInterface
    {
        $sessionContainerChanged = $sessionContainer->hasChanged();

        if ($sessionContainerChanged && $sessionContainer->isEmpty()) {
            return FigResponseCookies::set($response, $this->getExpirationCookie());
        }

        if ($sessionContainerChanged || ($this->shouldTokenBeRefreshed($token) && !$sessionContainer->isEmpty())) {
            return FigResponseCookies::set($response, $this->getTokenCookie($sessionContainer));
        }

        return $response;
    }

    private function shouldTokenBeRefreshed(Token $token = null) : bool
    {
        if (is_null($token)) {
            return false;
        }

        if (!$token->hasClaim(self::ISSUED_AT_CLAIM)) {
            return false;
        }

        return time() >= ($token->getClaim(self::ISSUED_AT_CLAIM) + $this->refreshTime);
    }

    private function getTokenCookie(SessionInterface $sessionContainer) : SetCookie
    {
        $timestamp = time();
        return $this
            ->defaultCookie
            ->withValue(
                (new Builder())
                    ->setIssuedAt($timestamp)
                    ->setExpiration($timestamp + $this->expirationTime)
                    ->set(self::SESSION_CLAIM, $sessionContainer->toArray())
                    ->sign($this->signer, $this->signatureKey)
                    ->getToken()
            )
            ->withExpires($timestamp + $this->expirationTime);
    }

    private function getExpirationCookie() : SetCookie
    {
        $expirationDate = new \DateTime('-30 days');
        return $this
            ->defaultCookie
            ->withValue(null)
            ->withExpires($expirationDate->getTimestamp());
    }
}
