================
Middle Framework
================

A micro-framework build around the idea of middlewares, basicly: MIDDLEWARE ALL
THE THINGS. What does that mean? Everything is based around simple interfaces
for which the default implementation can can be either replaced or decorated.
Also every component is NIH, using PSR-1, PSR-2, PSR-3, PSR-4, PSR-7 based and
only PHP 7.0 and higher (probably 7.1+ once that's released).

For example, the heart of an application build with this is the
``ApplicationInterface`` which just takes a PSR ``ServerRequestInterface`` and
returns a ``ResponseInterface``. The example below builds an application using
3 middlewares that add support for routing, sessions & error handling.

.. code-block:: php

    <?php

    // This can only lookup request attribute 'controller' and execute it to
    // generate the Response
    $app = jschreuder\Middle\ControllerRunner();

    // Let's add support for routing, which should add that controller
    // attribute. This also add a router which needs the base-URL, and a
    // handler for generating a response when no route is matched.
    $app = jschreuder\Middle\Router\RoutingMiddleware(
        $app,
        new jschreuder\Middle\Router\SymfonyRouter('http://localhost'),
        function () {
            return new Zend\Diactoros\Response\JsonResponse(['error'], 400);
        }
    );

    // Now let's also support using sessions, a Session instance is registered
    // to the Request's attributes with this Middleware.
    $app = jschreuder\Middle\Session\ZendSession($app, 0);

    // And finally: make sure any errors are caught, passed on to a PSR-3
    // compatible logger and return something readable to the end-user.
    $app = jschreuder\Middle\ErrorHandlerMiddleware(
        $app,
        new Monolog\Logger(...),
        function (ServerRequestInterface $request, \Throwable $exception) {
            return new Zend\Diactoros\Response\JsonResponse(['error'], 500);
        }
    );

With all that you have an application setup with incredibly simple parts that
are easily amended, swichted-out, etc.

Now let's run this thing:

.. code-block:: php

    <?php
    // Create Request
    $request = Zend\Diactoros\ServerRequestFactory::fromGlobals();

    // Render the response
    $response = $app->execute($request);

    // And output it
    (new \Zend\Diactoros\Response\SapiEmitter())->emit($response);
