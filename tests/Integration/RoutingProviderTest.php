
<?php

use jschreuder\Middle\Router\SymfonyRouter;
use jschreuder\Middle\Router\RoutingProviderInterface;
use jschreuder\Middle\Router\RouterInterface;
use jschreuder\Middle\Controller\CallableController;

test('it can use routing providers to organize routes', function () {
    $router = new SymfonyRouter('http://localhost');
    
    // Create an API routing provider
    $apiProvider = new class implements RoutingProviderInterface {
        public function registerRoutes(RouterInterface $router): void {
            $router->get('api.status', '/api/status', 
                CallableController::factoryFromCallable(fn() => 
                    Mockery::mock(\Psr\Http\Message\ResponseInterface::class)
                )
            );
            
            $router->post('api.users', '/api/users',
                CallableController::factoryFromCallable(fn() => 
                    Mockery::mock(\Psr\Http\Message\ResponseInterface::class)
                )
            );
        }
    };
    
    // Create a web routing provider
    $webProvider = new class implements RoutingProviderInterface {
        public function registerRoutes(RouterInterface $router): void {
            $router->get('home', '/',
                CallableController::factoryFromCallable(fn() => 
                    Mockery::mock(\Psr\Http\Message\ResponseInterface::class)
                )
            );
        }
    };
    
    $router->registerRoutes($apiProvider);
    $router->registerRoutes($webProvider);
    
    // Test API route
    $request = Mockery::mock(\Psr\Http\Message\ServerRequestInterface::class);
    $uri = Mockery::mock(\Psr\Http\Message\UriInterface::class);
    
    $uri->shouldReceive('getPath')->andReturn('/api/status');
    $uri->shouldReceive('getHost')->andReturn('localhost');
    $uri->shouldReceive('getScheme')->andReturn('http');
    $uri->shouldReceive('getQuery')->andReturn('');
    
    $request->shouldReceive('getUri')->andReturn($uri);
    $request->shouldReceive('getMethod')->andReturn('GET');
    
    $match = $router->parseRequest($request);
    expect($match->isMatch())->toBeTrue();
});
