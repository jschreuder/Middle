<?php declare(strict_types=1);

namespace jschreuder\Middle\ServerMiddleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class JsonRequestParserMiddleware implements MiddlewareInterface
{
    /** @param  string[]  $jsonContentTypes  array of regexes to check against content-types */
    public function __construct(
        private array $jsonContentTypes = ['#^application\/json(;|$)#iD']
    ) {}

    #[\Override]
    public function process(ServerRequestInterface $request, RequestHandlerInterface $requestHandler): ResponseInterface
    {
        if ($this->isJsonRequest($request->getHeaderLine('Content-Type'))) {
            $request = $request->withParsedBody($this->parseBody($request));
        }
        return $requestHandler->handle($request);
    }

    private function isJsonRequest(string $requestContentType) : bool
    {
        foreach ($this->jsonContentTypes as $jsonContentType) {
            if (preg_match($jsonContentType, $requestContentType) > 0) {
                return true;
            }
        }
        return false;
    }

    private function parseBody(ServerRequestInterface $request) : array
    {
        $parsedBody = json_decode($request->getBody()->getContents(), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \InvalidArgumentException('Could not decode JSON body: ' . json_last_error_msg());
        }
        if (!is_array($parsedBody)) {
            throw new \InvalidArgumentException('JSON body must be an object or array');
        }

        return $parsedBody;
    }
}
