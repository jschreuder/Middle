<?php declare(strict_types = 1);

namespace jschreuder\Middle;

use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

final class RequestHandler implements RequestHandlerInterface
{
    private \SplStack $stack;
    private bool $called = false;

    public function __construct(\SplStack $stack)
    {
        $this->stack = $stack;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if ($this->stack->count() === 0) {
            throw new \RuntimeException('No more middleware\'s to call on.');
        }
        if ($this->called) {
            throw new \RuntimeException('Already processed, cannot be ran twice.');
        }

        /** @var  MiddlewareInterface $next */
        $next = $this->stack->pop();
        $this->called = true;

        return $next->process($request, new self($this->stack));
    }
}
