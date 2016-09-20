<?php declare(strict_types = 1);

namespace jschreuder\Middle\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class CallableController implements ControllerInterface
{
    public static function fromCallable(callable $closure) : self
    {
        return new self($closure);
    }

    /** @var  callable */
    private $closure;

    private function __construct(callable $closure)
    {
        $this->closure = $closure;
    }

    public function execute(ServerRequestInterface $request) : ResponseInterface
    {
        return ($this->closure)($request);
    }
}
