<?php declare(strict_types=1);

namespace jschreuder\Middle\Router;

use Symfony\Component\Routing\Generator\UrlGenerator as UrlGenerator;

final class SymfonyUrlGenerator implements UrlGeneratorInterface
{
    public function __construct(
        private readonly UrlGenerator $generator
    ) {}

    public function generatePath(string $name, array $options = []): string
    {
        return $this->generator->generate($name, $options, UrlGenerator::ABSOLUTE_PATH);
    }

    public function generateUrl(string $name, array $options = []): string
    {
        return $this->generator->generate($name, $options, UrlGenerator::ABSOLUTE_URL);
    }
}
