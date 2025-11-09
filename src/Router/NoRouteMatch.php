<?php declare(strict_types=1);

namespace jschreuder\Middle\Router;

final readonly class NoRouteMatch implements RouteMatchInterface
{
    #[\Override]
    public function getName(): string
    {
        return "";
    }

    #[\Override]
    public function isMatch(): false
    {
        return false;
    }

    #[\Override]
    public function getController(): never
    {
        throw new \RuntimeException(
            "An unmatched route does not have a controller",
        );
    }

    #[\Override]
    public function getAttributes(): never
    {
        throw new \RuntimeException(
            "An unmatched route does not have attributes",
        );
    }
}
