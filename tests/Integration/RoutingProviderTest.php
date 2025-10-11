
<?php

use jschreuder\Middle\ApplicationStack;
use jschreuder\Middle\Router\SymfonyRouter;
use jschreuder\Middle\Router\RoutingProviderInterface;
use jschreuder\Middle\Router\RouterInterface;
use jschreuder\Middle\Controller\CallableController;
use jschreuder\Middle\Controller\ControllerRunner;
use jschreuder\Middle\Router\RoutingProviderCollection;
use jschreuder\Middle\ServerMiddleware\RoutingMiddleware;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\Uri;
use Psr\Http\Message\ResponseInterface;

test('it can use routing providers to organize routes', function () {
    $router = new SymfonyRouter('http://localhost');
    
    // Create an API routing provider
    $apiResponse = Mockery::mock(ResponseInterface::class);
    $apiProvider = new class($apiResponse) implements RoutingProviderInterface {
        public function __construct(private ResponseInterface $response) {}
        
        public function registerRoutes(RouterInterface $router): void {
            $router->get('api.status', '/api/status', 
                CallableController::factoryFromCallable(function() {
                    return $this->response;
                })
            );
        }
    };
    
    // Create a web routing provider
    $webResponse = Mockery::mock(ResponseInterface::class);
    $webProvider = new class($webResponse) implements RoutingProviderInterface {
        public function __construct(private ResponseInterface $response) {}
        
        public function registerRoutes(RouterInterface $router): void {
            $router->get('home', '/',
                CallableController::factoryFromCallable(function() {
                    return $this->response;
                })
            );
        }
    };
    
    $router->registerRoutes($apiProvider);
    $router->registerRoutes($webProvider);
    
    $fallback = CallableController::fromCallable(fn() => Mockery::mock(ResponseInterface::class));
    
    $app = new ApplicationStack(new ControllerRunner());
    $app = $app->withMiddleware(new RoutingMiddleware($router, $fallback));
    
    // Test API route actually executes
    $request = new ServerRequest([], [], new Uri('http://localhost/api/status'), 'GET');
    $response = $app->process($request);
    expect($response)->toBe($apiResponse);
    expect($response)->not->toBe($webResponse);
});

test('it can combine multiple routing providers with collection', function () {
    $router = new SymfonyRouter('http://localhost');
    
    $apiResponse = Mockery::mock(ResponseInterface::class);
    $apiProvider = new class($apiResponse) implements RoutingProviderInterface {
        public function __construct(private ResponseInterface $response) {}
        
        public function registerRoutes(RouterInterface $router): void {
            $router->get('api.status', '/api/status', 
                CallableController::factoryFromCallable(fn() => $this->response)
            );
        }
    };
    
    $webResponse = Mockery::mock(ResponseInterface::class);
    $webProvider = new class($webResponse) implements RoutingProviderInterface {
        public function __construct(private ResponseInterface $response) {}
        
        public function registerRoutes(RouterInterface $router): void {
            $router->get('home', '/',
                CallableController::factoryFromCallable(fn() => $this->response)
            );
        }
    };
    
    // Use collection to register both
    $collection = new RoutingProviderCollection($apiProvider, $webProvider);
    $router->registerRoutes($collection);
    
    $fallback = CallableController::fromCallable(fn() => 
        Mockery::mock(ResponseInterface::class)
    );
    
    $app = new ApplicationStack(new ControllerRunner());
    $app = $app->withMiddleware(new RoutingMiddleware($router, $fallback));
    
    // Verify both providers' routes work
    $apiRequest = new ServerRequest([], [], new Uri('http://localhost/api/status'), 'GET');
    $apiResult = $app->process($apiRequest);
    expect($apiResult)->toBe($apiResponse);
    
    $webRequest = new ServerRequest([], [], new Uri('http://localhost/'), 'GET');
    $webResult = $app->process($webRequest);
    expect($webResult)->toBe($webResponse);
});
