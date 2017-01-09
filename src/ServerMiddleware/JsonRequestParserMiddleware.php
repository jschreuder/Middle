<?php declare(strict_types = 1);

namespace jschreuder\Middle\ServerMiddleware;

use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class JsonRequestParserMiddleware implements MiddlewareInterface
{
    /** @var  string[] */
    private $jsonContentTypes;

    public function __construct(array $jsonContentTypes = null)
    {
        if (is_null($jsonContentTypes)) {
            $jsonContentTypes = ['application/json'];
        }
        $this->jsonContentTypes = $jsonContentTypes;
    }

    public function process(ServerRequestInterface $request, DelegateInterface $delegate): ResponseInterface
    {
        if (in_array($request->getHeaderLine('Content-Type'), $this->jsonContentTypes, true)) {
            $request = $request->withParsedBody($this->parseBody($request));
        }
        return $delegate->process($request);
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
