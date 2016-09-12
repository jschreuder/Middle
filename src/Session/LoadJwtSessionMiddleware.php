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
use PSR7Session\Session\DefaultSessionData;
use PSR7Session\Session\LazySession;
use PSR7Session\Session\SessionInterface as JwtSessionInterface;
use PSR7Session\Time\CurrentTimeProviderInterface;
use PSR7Session\Time\SystemCurrentTime;

class LoadJwtSessionMiddleware implements HttpMiddlewareInterface
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

    /** @var  CurrentTimeProviderInterface */
    private $currentTimeProvider;

    public function __construct(
        Signer $signer,
        string $signatureKey,
        string $verificationKey,
        SetCookie $defaultCookie,
        Parser $tokenParser,
        int $expirationTime,
        CurrentTimeProviderInterface $currentTimeProvider,
        int $refreshTime = self::DEFAULT_REFRESH_TIME
    ) {
        $this->signer              = $signer;
        $this->signatureKey        = $signatureKey;
        $this->verificationKey     = $verificationKey;
        $this->tokenParser         = $tokenParser;
        $this->defaultCookie       = clone $defaultCookie;
        $this->expirationTime      = $expirationTime;
        $this->currentTimeProvider = $currentTimeProvider;
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
            $expirationTime,
            new SystemCurrentTime()
        );
    }

    public function process(ServerRequestInterface $request, DelegateInterface $delegate) : ResponseInterface
    {
        $token            = $this->parseToken($request);
        $sessionContainer = LazySession::fromContainerBuildingCallback(function () use ($token) : JwtSessionInterface {
            return $this->extractSessionContainer($token);
        });

        $response = $delegate->next($request->withAttribute('session', new JwtSession($sessionContainer)));

        return $this->appendToken($sessionContainer, $response, $token);
    }

    /** @return  ?Token */
    private function parseToken(ServerRequestInterface $request)
    {
        $cookies    = $request->getCookieParams();
        $cookieName = $this->defaultCookie->getName();

        if (! isset($cookies[$cookieName])) {
            return null;
        }

        try {
            $token = $this->tokenParser->parse($cookies[$cookieName]);
        } catch (\InvalidArgumentException $invalidToken) {
            return null;
        }

        if (! $token->validate(new ValidationData())) {
            return null;
        }

        return $token;
    }

    public function extractSessionContainer(Token $token = null) : JwtSessionInterface
    {
        try {
            if (null === $token || ! $token->verify($this->signer, $this->verificationKey)) {
                return DefaultSessionData::newEmptySession();
            }

            return DefaultSessionData::fromDecodedTokenData(
                (object) $token->getClaim(self::SESSION_CLAIM, new \stdClass())
            );
        } catch (\BadMethodCallException $invalidToken) {
            return DefaultSessionData::newEmptySession();
        }
    }

    private function appendToken(
        JwtSessionInterface $sessionContainer,
        ResponseInterface $response,
        Token $token = null
    ) : ResponseInterface
    {
        $sessionContainerChanged = $sessionContainer->hasChanged();

        if ($sessionContainerChanged && $sessionContainer->isEmpty()) {
            return FigResponseCookies::set($response, $this->getExpirationCookie());
        }

        if ($sessionContainerChanged || ($this->shouldTokenBeRefreshed($token) && ! $sessionContainer->isEmpty())) {
            return FigResponseCookies::set($response, $this->getTokenCookie($sessionContainer));
        }

        return $response;
    }

    /**
     * {@inheritDoc}
     */
    private function shouldTokenBeRefreshed(Token $token = null) : bool
    {
        if (null === $token) {
            return false;
        }

        if (! $token->hasClaim(self::ISSUED_AT_CLAIM)) {
            return false;
        }

        return $this->timestamp() >= ($token->getClaim(self::ISSUED_AT_CLAIM) + $this->refreshTime);
    }

    private function getTokenCookie(JwtSessionInterface $sessionContainer) : SetCookie
    {
        $timestamp = $this->timestamp();

        return $this
            ->defaultCookie
            ->withValue(
                (new Builder())
                    ->setIssuedAt($timestamp)
                    ->setExpiration($timestamp + $this->expirationTime)
                    ->set(self::SESSION_CLAIM, $sessionContainer)
                    ->sign($this->signer, $this->signatureKey)
                    ->getToken()
            )
            ->withExpires($timestamp + $this->expirationTime);
    }

    /**
     * @return SetCookie
     */
    private function getExpirationCookie() : SetCookie
    {
        $currentTimeProvider = $this->currentTimeProvider;
        $expirationDate      = $currentTimeProvider();
        $expirationDate      = $expirationDate->modify('-30 days');

        return $this
            ->defaultCookie
            ->withValue(null)
            ->withExpires($expirationDate->getTimestamp());
    }

    private function timestamp() : int
    {
        $currentTimeProvider = $this->currentTimeProvider;

        return $currentTimeProvider()->getTimestamp();
    }
}
