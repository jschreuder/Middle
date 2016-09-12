<?php declare(strict_types = 1);

namespace jschreuder\Middle;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ApplicationStack implements ApplicationStackInterface
{
    /** @var  \SplStack | HttpMiddlewareInterface[] */
    private $stack;

    public function __construct(array $middlewares = [])
    {
        $this->stack = new \SplStack();
        foreach ($middlewares as $middleware) {
            if (!$middleware instanceof HttpMiddlewareInterface) {
                throw new \InvalidArgumentException('All middlewares must implement ApplicationInterface');
            }
            $this->stack->push($middleware);
        }
    }

    public function withMiddleware(HttpMiddlewareInterface $middleware) : ApplicationStack
    {
        $stack = clone $this;
        $stack->stack = clone $this->stack;
        $stack->stack->push($middleware);
        return $stack;
    }

    public function withoutMiddleware(HttpMiddlewareInterface $middleware) : ApplicationStack
    {
        $oldStack = clone $this->stack;
        $newStack = new \SplStack();
        while ($middlewareInstance = $oldStack->shift()) {
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

        /** @var  HttpMiddlewareInterface $current */
        $current = $stack->pop();
        $response = $current->process($request, new Delegate($stack));

        return $response;
    }
}
