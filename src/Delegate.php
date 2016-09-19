<?php declare(strict_types = 1);

namespace jschreuder\Middle;

use Interop\Http\Middleware\DelegateInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final class Delegate implements DelegateInterface
{
    /** @var  \SplStack */
    private $stack;

    public function __construct(\SplStack $stack)
    {
        $this->stack = $stack;
    }

    public function next(RequestInterface $request) : ResponseInterface
    {
        if ($this->stack->count() === 0) {
            throw new \RuntimeException('No more middleware\'s to call on.');
        }

        /** @var  HttpMiddlewareInterface $next */
        $next = $this->stack->pop();
        return $next->process($request, $this);
    }
}
