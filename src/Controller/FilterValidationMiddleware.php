<?php declare(strict_types = 1);

namespace jschreuder\Middle\Controller;

use jschreuder\Middle\ApplicationInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class FilterValidationMiddleware implements ApplicationInterface
{
    /** @var  ApplicationInterface */
    private $application;

    /** @var  callable */
    private $errorHandler;

    public function __construct(ApplicationInterface $application, callable $errorHandler)
    {
        $this->application = $application;
        $this->errorHandler = $errorHandler;
    }

    public function execute(ServerRequestInterface $request) : ResponseInterface
    {
        $controller = $request->getAttribute('controller');

        if ($controller instanceof RequestFilterInterface) {
            // Execute the request-filter from the controller
            $request = $controller->filterRequest($request);
        }

        if ($controller instanceof RequestValidatorInterface) {
            // Execute the request-validator from the controller
            try {
                $controller->validateRequest($request);
            } catch (ValidationFailedException $exception) {
                // Hand of to error-handler on failure
                return ($this->errorHandler)($request, $exception);
            }
        }

        // Filtered and validated (if applicable), let's continue on
        return $this->application->execute($request);
    }
}
