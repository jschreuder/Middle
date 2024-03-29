<?php declare(strict_types=1);

namespace jschreuder\Middle\ServerMiddleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class JsonRequestParserMiddleware implements MiddlewareInterface
{
    /** @var  string[]  array of regexes to check against content-types */
    private array $jsonContentTypes;

    public function __construct(array $jsonContentTypes = null)
    {
        if (is_null($jsonContentTypes)) {
            $jsonContentTypes = ['#^application\/json(;|$)#iD'];
        }
        $this->jsonContentTypes = $jsonContentTypes;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $requestHandler): ResponseInterface
    {
        if ($this->isJsonRequest($request->getHeaderLine('Content-Type'))) {
            $request = $request->withParsedBody($this->parseBody($request));
        }
        return $requestHandler->handle($request);
    }

    private function isJsonRequest(?string $requestContentType) : bool
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

        return $parsedBody;
    }
}
