
<?php

use jschreuder\Middle\Router\SymfonyRouter;
use jschreuder\Middle\Router\RoutingProviderInterface;
use jschreuder\Middle\Router\RouterInterface;
use jschreuder\Middle\Controller\CallableController;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\StreamFactory;
use Laminas\Diactoros\Uri;

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
    $request = new ServerRequest([], [], new Uri('http://localhost/api/status'), 'GET', (new StreamFactory)->createStream(''), []);
    $match = $router->parseRequest($request);
    expect($match->isMatch())->toBeTrue();
});
