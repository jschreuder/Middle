<?php declare(strict_types=1);

namespace jschreuder\Middle\Router;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface as SymfonyUrlGeneratorInterface;

final readonly class SymfonyUrlGenerator implements UrlGeneratorInterface
{
    public function __construct(
        private SymfonyUrlGeneratorInterface $generator,
    ) {}

    #[\Override]
    public function generatePath(string $name, array $options = []): string
    {
        return $this->generator->generate(
            $name,
            $options,
            SymfonyUrlGeneratorInterface::ABSOLUTE_PATH,
        );
    }

    #[\Override]
    public function generateUrl(string $name, array $options = []): string
    {
        return $this->generator->generate(
            $name,
            $options,
            SymfonyUrlGeneratorInterface::ABSOLUTE_URL,
        );
    }
}
