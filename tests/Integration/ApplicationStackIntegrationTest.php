<?php

use jschreuder\Middle\ApplicationStack;
use jschreuder\Middle\Controller\CallableController;
use jschreuder\Middle\Controller\ControllerInterface;
use jschreuder\Middle\Controller\ControllerRunner;
use jschreuder\Middle\Router\SymfonyRouter;
use jschreuder\Middle\ServerMiddleware\ErrorHandlerMiddleware;
use jschreuder\Middle\ServerMiddleware\JsonRequestParserMiddleware;
use jschreuder\Middle\ServerMiddleware\RequestFilterMiddleware;
use jschreuder\Middle\ServerMiddleware\RequestValidatorMiddleware;
use jschreuder\Middle\ServerMiddleware\RoutingMiddleware;
use jschreuder\Middle\ServerMiddleware\SessionMiddleware;
use jschreuder\Middle\Session\Session;
use jschreuder\Middle\Session\SessionProcessorInterface;
use jschreuder\Middle\Controller\RequestFilterInterface;
use jschreuder\Middle\Controller\RequestValidatorInterface;
use jschreuder\Middle\Exception\ValidationFailedException;
use jschreuder\Middle\Session\SessionInterface;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\StreamFactory;
use Laminas\Diactoros\Uri;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\NullLogger;

test('it can process a complete request through the full middleware stack', function () {
    // Setup router
    $router = new SymfonyRouter('http://localhost');
    
    // Track middleware execution order
    $executionOrder = [];
    
    // Create a controller that uses filtering and validation
    $expectedResponse = Mockery::mock(ResponseInterface::class);
    $expectedResponse->shouldReceive('withHeader')->andReturnSelf();
    $controller = new class($executionOrder, $expectedResponse) implements 
        ControllerInterface, 
        RequestFilterInterface, 
        RequestValidatorInterface 
    {
        public function __construct(
            private array &$executionOrder,
            private ResponseInterface $response
        ) {}
        
        public function filterRequest(ServerRequestInterface $request): ServerRequestInterface 
        {
            $this->executionOrder[] = 'filter';
            $body = $request->getParsedBody();
            $body['name'] = trim($body['name'] ?? '');
            return $request->withParsedBody($body);
        }
        
        public function validateRequest(ServerRequestInterface $request): void 
        {
            $this->executionOrder[] = 'validate';
            $body = $request->getParsedBody();
            if (empty($body['name'])) {
                throw new ValidationFailedException(['name' => 'required']);
            }
        }
        
        public function execute(ServerRequestInterface $request): ResponseInterface 
        {
            $this->executionOrder[] = 'execute';
            $body = $request->getParsedBody();
            $session = $request->getAttribute('session');
            
            
            // Verify session is available
            expect($session)->toBeInstanceOf(SessionInterface::class);
            expect($body['name'])->toBe('John Doe');
            
            return $this->response;
        }
    };
    
    // Register route
    $router->post('test', '/test', fn() => $controller);
    
    // Fallback controller
    $fallbackResponse = Mockery::mock(ResponseInterface::class);
    $fallback = CallableController::fromCallable(function() use ($fallbackResponse) {
        return $fallbackResponse;
    });
    
    // Error handler
    $errorResponse = Mockery::mock(ResponseInterface::class);
    $errorController = CallableController::fromCallable(function($request) use ($errorResponse) {
        return $errorResponse;
    });
    
    // Session processor
    $sessionProcessor = new class implements SessionProcessorInterface {
        public function processRequest(ServerRequestInterface $request): ServerRequestInterface 
        {
            return $request->withAttribute('session', new Session());
        }
        
        public function processResponse(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface 
        {
            return $response;
        }
    };
    
    // Build the full application stack (LIFO order - last added runs first)
    $app = new ApplicationStack(new ControllerRunner());
    $app = $app->withMiddleware(new RequestValidatorMiddleware(function ($request, $exception) {
        throw $exception; // Let error handler catch it
    }));
    $app = $app->withMiddleware(new RequestFilterMiddleware());
    $app = $app->withMiddleware(new RoutingMiddleware($router, $fallback));
    $app = $app->withMiddleware(new SessionMiddleware($sessionProcessor));
    $app = $app->withMiddleware(new JsonRequestParserMiddleware());
    $app = $app->withMiddleware(new ErrorHandlerMiddleware(new NullLogger(), $errorController));
    
    // Create mock request
    $request = new ServerRequest([], [], new Uri('http://localhost/test'), 'POST', (new StreamFactory)->createStream('{"name":"  John Doe  "}'), [
        'Content-Type' => 'application/json'
    ]);
    
    // Process request through the entire stack, test wether we got the right response
    $response = $app->process($request);
    expect($response)->not->toBe($errorResponse);
    expect($response)->not->toBe($fallbackResponse);
    expect($response)->toBe($expectedResponse);
    
    // Verify execution order: filter -> validate -> execute
    expect($executionOrder)->toBe(['filter', 'validate', 'execute']);
    expect($response)->toBeInstanceOf(ResponseInterface::class);
});

test('it handles routing to fallback when no route matches', function () {
    $router = new SymfonyRouter('http://localhost');
    
    $fallbackExecuted = false;
    $fallback = CallableController::fromCallable(function () use (&$fallbackExecuted) {
        $fallbackExecuted = true;
        $response = Mockery::mock(ResponseInterface::class);
        return $response;
    });
    
    $app = new ApplicationStack(new ControllerRunner());
    $app = $app->withMiddleware(new RoutingMiddleware($router, $fallback));
    
    $request = Mockery::mock(ServerRequestInterface::class);
    $uri = Mockery::mock(\Psr\Http\Message\UriInterface::class);
    
    $uri->shouldReceive('getPath')->andReturn('/nonexistent');
    $uri->shouldReceive('getHost')->andReturn('localhost');
    $uri->shouldReceive('getScheme')->andReturn('http');
    $uri->shouldReceive('getQuery')->andReturn('');
    
    $request->shouldReceive('getUri')->andReturn($uri);
    $request->shouldReceive('getMethod')->andReturn('GET');
    $request->shouldReceive('withAttribute')->andReturnUsing(function ($key, $value) use ($request) {
        $newRequest = clone $request;
        $newRequest->shouldReceive('getAttribute')->with($key)->andReturn($value);
        $newRequest->shouldReceive('withAttribute')->andReturnSelf();
        return $newRequest;
    });
    
    $response = $app->process($request);
    
    expect($fallbackExecuted)->toBeTrue();
});

test('it handles exceptions through error handler middleware', function () {
    $router = new SymfonyRouter('http://localhost');
    
    $errorHandled = false;
    $errorController = CallableController::fromCallable(function ($request) use (&$errorHandled) {
        $errorHandled = true;
        $error = $request->getAttribute('error');
        expect($error)->toBeInstanceOf(\RuntimeException::class);
        expect($error->getMessage())->toBe('Test exception');
        
        $response = Mockery::mock(ResponseInterface::class);
        return $response;
    });
    
    $throwingController = CallableController::fromCallable(function () {
        throw new \RuntimeException('Test exception');
    });
    
    $router->get('test', '/test', fn() => $throwingController);
    
    $fallback = CallableController::fromCallable(function () {
        $response = Mockery::mock(ResponseInterface::class);
        return $response;
    });
    
    $app = new ApplicationStack(new ControllerRunner());
    $app = $app->withMiddleware(new RoutingMiddleware($router, $fallback));
    $app = $app->withMiddleware(new ErrorHandlerMiddleware(new NullLogger(), $errorController));
    
    $request = Mockery::mock(ServerRequestInterface::class);
    $uri = Mockery::mock(\Psr\Http\Message\UriInterface::class);
    
    $uri->shouldReceive('getPath')->andReturn('/test');
    $uri->shouldReceive('getHost')->andReturn('localhost');
    $uri->shouldReceive('getScheme')->andReturn('http');
    $uri->shouldReceive('getQuery')->andReturn('');
    
    $request->shouldReceive('getUri')->andReturn($uri);
    $request->shouldReceive('getMethod')->andReturn('GET');
    $request->shouldReceive('withAttribute')->andReturnUsing(function ($key, $value) use ($request) {
        $newRequest = clone $request;
        $newRequest->shouldReceive('getAttribute')->with($key)->andReturn($value);
        $newRequest->shouldReceive('withAttribute')->andReturnSelf();
        return $newRequest;
    });
    
    $response = $app->process($request);
    
    expect($errorHandled)->toBeTrue();
});
