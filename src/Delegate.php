<?php declare(strict_types = 1);

namespace jschreuder\Middle;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class Delegate implements DelegateInterface
{
    /** @var  \SplStack */
    private $stack;

    public function __construct(\SplStack $stack)
    {
        $this->stack = $stack;
    }

    public function next(ServerRequestInterface $request) : ResponseInterface
    {
        if ($this->stack->count() === 0) {
            throw new \RuntimeException('No more middleware\'s to call on.');
        }

        /** @var  HttpMiddlewareInterface $next */
        $next = $this->stack->pop();
        return $next->process($request, $this);
    }
}
