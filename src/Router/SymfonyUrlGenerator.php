<?php declare(strict_types=1);

namespace jschreuder\Middle\Router;

use Symfony\Component\Routing\Generator\UrlGenerator as UrlGenerator;

final readonly class SymfonyUrlGenerator implements UrlGeneratorInterface
{
    public function __construct(
        private UrlGenerator $generator
    ) {}

    #[\Override]
    public function generatePath(string $name, array $options = []): string
    {
        return $this->generator->generate($name, $options, UrlGenerator::ABSOLUTE_PATH);
    }

    #[\Override]
    public function generateUrl(string $name, array $options = []): string
    {
        return $this->generator->generate($name, $options, UrlGenerator::ABSOLUTE_URL);
    }
}
