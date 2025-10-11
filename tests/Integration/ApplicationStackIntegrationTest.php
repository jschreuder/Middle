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
    
    // Create request
    $request = new ServerRequest([], [], new Uri('http://localhost/test'), 'POST', (new StreamFactory)->createStream('{"name":"  John Doe  "}'), [
        'Content-Type' => 'application/json'
    ]);
    
    // Process request through the entire stack and verify correct response
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
    
    $fallbackResponse = Mockery::mock(ResponseInterface::class);
    $fallback = CallableController::fromCallable(function () use ($fallbackResponse) {
        return $fallbackResponse;
    });
    
    $app = new ApplicationStack(new ControllerRunner());
    $app = $app->withMiddleware(new RoutingMiddleware($router, $fallback));
    
    $request = new ServerRequest([], [], new Uri('http://localhost/nonexistent'), 'GET', (new StreamFactory)->createStream(''), []);
    
    $response = $app->process($request);
    expect($response)->toBe($fallbackResponse);
});

test('it handles exceptions through error handler middleware', function () {
    $router = new SymfonyRouter('http://localhost');
    
    $errorResponse = Mockery::mock(ResponseInterface::class);
    $errorController = CallableController::fromCallable(function ($request) use ($errorResponse) {
        $error = $request->getAttribute('error');
        expect($error)->toBeInstanceOf(\RuntimeException::class);
        expect($error->getMessage())->toBe('Test exception');
        
        return $errorResponse;
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
    
    $request = new ServerRequest([], [], new Uri('http://localhost/test'), 'GET', (new StreamFactory)->createStream(''), []);
    $response = $app->process($request);
    
    expect($response)->toBe($errorResponse);
});

test('it handles validation failure through the full stack', function () {
    $router = new SymfonyRouter('http://localhost');
    
    $validationErrorResponse = Mockery::mock(ResponseInterface::class);
    $validatingController = new class($validationErrorResponse) implements 
        ControllerInterface, 
        RequestValidatorInterface 
    {
        public function __construct(private ResponseInterface $response) {}
        
        public function validateRequest(ServerRequestInterface $request): void {
            throw new ValidationFailedException(['email' => 'Invalid email']);
        }
        
        public function execute(ServerRequestInterface $request): ResponseInterface {
            throw new \RuntimeException('Should not execute');
        }
    };
    
    $router->post('test', '/test', fn() => $validatingController);
    
    $fallback = CallableController::fromCallable(fn() => 
        Mockery::mock(ResponseInterface::class)
    );
    
    $errorController = CallableController::fromCallable(fn() => 
        Mockery::mock(ResponseInterface::class)
    );
    
    $app = new ApplicationStack(new ControllerRunner());
    $app = $app->withMiddleware(new RequestValidatorMiddleware(
        function($request, $exception) use ($validationErrorResponse) {
            expect($exception)->toBeInstanceOf(ValidationFailedException::class);
            return $validationErrorResponse;
        }
    ));
    $app = $app->withMiddleware(new RoutingMiddleware($router, $fallback));
    $app = $app->withMiddleware(new ErrorHandlerMiddleware(new NullLogger(), $errorController));
    
    $request = new ServerRequest([], [], new Uri('http://localhost/test'), 'POST');
    $response = $app->process($request);
    
    expect($response)->toBe($validationErrorResponse);
});

test('it passes route parameters to controllers', function () {
    $router = new SymfonyRouter('http://localhost');
    
    $capturedId = null;
    $expectedResponse = Mockery::mock(ResponseInterface::class);
    $controller = CallableController::fromCallable(
        function(ServerRequestInterface $request) use (&$capturedId, $expectedResponse) {
            $capturedId = $request->getAttribute('id');
            return $expectedResponse;
        }
    );
    
    $router->get('user.show', '/users/{id}', fn() => $controller, [], ['id' => '\d+']);
    
    $fallback = CallableController::fromCallable(fn() => 
        Mockery::mock(ResponseInterface::class)
    );
    
    $app = new ApplicationStack(new ControllerRunner());
    $app = $app->withMiddleware(new RoutingMiddleware($router, $fallback));
    
    $request = new ServerRequest([], [], new Uri('http://localhost/users/123'), 'GET');
    $response = $app->process($request);
    
    expect($capturedId)->toBe('123');
    expect($response)->toBe($expectedResponse);
});
