<?php declare(strict_types = 1);

namespace jschreuder\Middle;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Zend\Diactoros\Response;

class ErrorHandlerMiddleware implements ApplicationInterface
{
    /** @var  ApplicationInterface */
    private $application;

    /** @var  LoggerInterface */
    private $logger;

    /** @var  \Twig_Environment */
    private $twig;

    public function __construct(ApplicationInterface $application, LoggerInterface $logger, \Twig_Environment $twig)
    {
        $this->application = $application;
        $this->logger = $logger;
        $this->twig = $twig;
    }

    public function execute(ServerRequestInterface $request) : ResponseInterface
    {
        try {
            return $this->application->execute($request);
        } catch (\Throwable $exception) {
            $this->logger->alert($exception->getMessage(), [
                'line' => $exception->getLine(),
                'file' => $exception->getFile(),
                'backtrace' => $exception->getTrace()
            ]);
            return new Response\HtmlResponse($this->twig->render('500.twig'), 500);
        }
    }
}
