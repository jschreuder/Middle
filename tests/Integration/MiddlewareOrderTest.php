<?php

use jschreuder\Middle\ApplicationStack;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

test("middleware executes in LIFO order", function () {
    $order = [];

    $middleware1 = new class ($order) implements MiddlewareInterface {
        public function __construct(private array &$order) {}

        public function process(
            ServerRequestInterface $request,
            RequestHandlerInterface $handler,
        ): ResponseInterface {
            $this->order[] = "before-1";
            $response = $handler->handle($request);
            $this->order[] = "after-1";
            return $response;
        }
    };

    $middleware2 = new class ($order) implements MiddlewareInterface {
        public function __construct(private array &$order) {}

        public function process(
            ServerRequestInterface $request,
            RequestHandlerInterface $handler,
        ): ResponseInterface {
            $this->order[] = "before-2";
            $response = $handler->handle($request);
            $this->order[] = "after-2";
            return $response;
        }
    };

    $expectedResponse = Mockery::mock(ResponseInterface::class);
    $middleware3 = new class ($order, $expectedResponse) implements
        MiddlewareInterface
    {
        public function __construct(
            private array &$order,
            private ResponseInterface $response,
        ) {}

        public function process(
            ServerRequestInterface $request,
            RequestHandlerInterface $handler,
        ): ResponseInterface {
            $this->order[] = "execute";
            return $this->response;
        }
    };

    // Add in order: 3, 2, 1 (but they execute in reverse: 1, 2, 3)
    $app = new ApplicationStack($middleware3);
    $app = $app->withMiddleware($middleware2);
    $app = $app->withMiddleware($middleware1);

    $request = Mockery::mock(ServerRequestInterface::class);
    $response = $app->process($request);
    expect($response)->toBe($expectedResponse);

    // Middleware 3 runs first, then 2, then 1
    // On the way back out: 1 finishes last, 2, then 3
    expect($order)->toBe([
        "before-1",
        "before-2",
        "execute",
        "after-2",
        "after-1",
    ]);
});
