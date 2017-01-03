<?php declare(strict_types = 1);

namespace jschreuder\Middle;

use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ApplicationStack implements ApplicationStackInterface
{
    /** @var  \SplStack | MiddlewareInterface[] */
    private $stack;

    public function __construct(array $middlewares = [])
    {
        $this->stack = new \SplStack();
        foreach ($middlewares as $middleware) {
            if (!$middleware instanceof MiddlewareInterface) {
                throw new \InvalidArgumentException('All middlewares must implement ApplicationInterface');
            }
            $this->stack->push($middleware);
        }
    }

    public function withMiddleware(MiddlewareInterface $middleware) : ApplicationStack
    {
        $stack = clone $this;
        $stack->stack = clone $this->stack;
        $stack->stack->push($middleware);
        return $stack;
    }

    public function withoutMiddleware(MiddlewareInterface $middleware) : ApplicationStack
    {
        $oldStack = clone $this->stack;
        $newStack = new \SplStack();
        while (!$oldStack->isEmpty()) {
            $middlewareInstance = $oldStack->shift();
            if ($middlewareInstance !== $middleware) {
                $newStack->push($middlewareInstance);
            }
        }

        $stack = clone $this;
        $stack->stack = $newStack;
        return $stack;
    }

    public function process(ServerRequestInterface $request) : ResponseInterface
    {
        if ($this->stack->count() === 0) {
            throw new \RuntimeException('Cannot process with an empty stack');
        }
        $stack = clone $this->stack;

        /** @var  MiddlewareInterface $current */
        $current = $stack->pop();
        $response = $current->process($request, new Delegate($stack));

        return $response;
    }
}
