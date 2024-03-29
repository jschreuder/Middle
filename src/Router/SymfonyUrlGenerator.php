<?php declare(strict_types=1);

namespace jschreuder\Middle\Router;

use Symfony\Component\Routing\Generator\UrlGenerator as UrlGenerator;

final class SymfonyUrlGenerator implements UrlGeneratorInterface
{
    private UrlGenerator $generator;

    public function __construct(UrlGenerator $generator)
    {
        $this->generator = $generator;
    }

    public function generatePath(string $name, array $options = []): string
    {
        return $this->generator->generate($name, $options, UrlGenerator::ABSOLUTE_PATH);
    }

    public function generateUrl(string $name, array $options = []): string
    {
        return $this->generator->generate($name, $options, UrlGenerator::ABSOLUTE_URL);
    }
}
