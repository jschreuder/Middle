<?php declare(strict_types = 1);

namespace jschreuder\Middle;

use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

// @todo prevent calling ->process() twice
final class Delegate implements DelegateInterface
{
    /** @var  \SplStack */
    private $stack;

    public function __construct(\SplStack $stack)
    {
        $this->stack = $stack;
    }

    public function process(ServerRequestInterface $request): ResponseInterface
    {
        if ($this->stack->count() === 0) {
            throw new \RuntimeException('No more middleware\'s to call on.');
        }

        /** @var  MiddlewareInterface $next */
        $next = $this->stack->pop();
        return $next->process($request, $this);
    }
}
