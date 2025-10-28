<?php declare(strict_types=1);

namespace jschreuder\Middle;

use jschreuder\Middle\Exception\ApplicationStackException;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

final class ApplicationStack implements ApplicationStackInterface
{
    private \SplStack $stack;
    private ?LoggerInterface $logger = null;

    public function __construct(MiddlewareInterface ...$middlewares)
    {
        $this->stack = new \SplStack();
        foreach ($middlewares as $middleware) {
            $this->stack->push($middleware);
        }
    }

    #[\Override]
    public function withLogger(LoggerInterface $logger): ApplicationStackInterface
    {
        $stack = clone $this;
        $stack->logger = $logger;
        return $stack;
    }

    #[\Override]
    public function withMiddleware(MiddlewareInterface $middleware): ApplicationStackInterface
    {
        $stack = clone $this;
        $stack->stack = clone $this->stack;
        $stack->stack->push($middleware);
        return $stack;
    }

    #[\Override]
    public function withoutMiddleware(MiddlewareInterface $middleware): ApplicationStackInterface
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

    #[\Override]
    public function process(ServerRequestInterface $request): ResponseInterface
    {
        if ($this->stack->count() === 0) {
            throw new ApplicationStackException('Cannot process with an empty stack');
        }
        $stack = clone $this->stack;

        /** @var  MiddlewareInterface $current */
        $current = $stack->pop();
        return $current->process($request, new RequestHandler($stack, $this->logger));
    }
}
