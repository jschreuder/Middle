<?php declare(strict_types = 1);

namespace jschreuder\Middle\Application\Router;

use Symfony\Component\Routing\Generator\UrlGenerator as UrlGenerator;

class SymfonyUrlGenerator implements UrlGeneratorInterface
{
    /** @var  SymfonyUrlGenerator */
    private $generator;

    public function __construct(UrlGenerator $generator)
    {
        $this->generator = $generator;
    }

    public function generatePath(string $name, array $options = []) : string
    {
        return $this->generator->generate($name, $options, UrlGenerator::ABSOLUTE_PATH);
    }

    public function generateUrl(string $name, array $options = []) : string
    {
        return $this->generator->generate($name, $options, UrlGenerator::ABSOLUTE_URL);
    }
}
