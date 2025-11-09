<?php declare(strict_types=1);

namespace jschreuder\Middle;

use jschreuder\Middle\Exception\ApplicationStackException;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use SplStack;

final class RequestHandler implements RequestHandlerInterface
{
    private bool $called = false;

    public function __construct(
        private readonly SplStack $stack,
        private readonly ?LoggerInterface $logger = null,
    ) {}

    #[\Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if ($this->stack->count() === 0) {
            throw new ApplicationStackException(
                "No more middlewares to call on.",
            );
        }
        if ($this->called) {
            throw new ApplicationStackException(
                "Already processed, cannot be run twice.",
            );
        }

        /** @var  MiddlewareInterface $next */
        $next = $this->stack->pop();
        $this->called = true;

        $this->logger?->debug("Middleware started: " . \get_class($next));
        $response = $next->process(
            $request,
            new self($this->stack, $this->logger),
        );
        $this->logger?->debug("Middleware finished: " . \get_class($next));
        return $response;
    }
}
