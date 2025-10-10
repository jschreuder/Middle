<?php declare(strict_types=1);

namespace jschreuder\Middle\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class CallableController implements ControllerInterface
{
    public static function fromCallable(\Closure $closure): self
    {
        return new self($closure);
    }

    public static function factoryFromCallable(\Closure $closure): callable
    {
        return function () use ($closure) {
            return self::fromCallable($closure);
        };
    }

    private function __construct(
        private readonly \Closure $closure
    ) {}

    public function execute(ServerRequestInterface $request): ResponseInterface
    {
        return ($this->closure)($request);
    }
}
