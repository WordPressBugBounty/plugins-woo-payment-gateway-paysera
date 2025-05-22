<?php

declare(strict_types=1);

namespace Paysera\Service\SelfDiagnosis\Library\Service;

use Paysera\Scoped\Psr\Container\ContainerInterface;
use Paysera\Service\SelfDiagnosis\Library\CompatibilityCheckerInterface;
use Paysera\Service\SelfDiagnosis\Library\CompatibilityCheckerRegistry;

class CompatibilityCheckerManager
{
    private ContainerInterface $container;
    private CompatibilityCheckerRegistry $checkerRegistry;

    public function __construct(
        ContainerInterface $container,
        CompatibilityCheckerRegistry $checkerRegistry
    ) {
        $this->container = $container;
        $this->checkerRegistry = $checkerRegistry;
    }

    public function runChecks(): array
    {
        $results = [];
        foreach ($this->checkerRegistry->getCheckers() as $category => $checkers) {
            $results[$category] = array_map(
                fn($checkerClass) => $this->getCheckerInstance($checkerClass)->check(),
                $checkers
            );
        }

        return $results;
    }

    public function getCheckerInstance(string $checkerClass): CompatibilityCheckerInterface
    {
        if ($this->container->has($checkerClass) === false) {
            throw new \RuntimeException(sprintf('Checker "%s" not found in container', $checkerClass));
        }

        $checker = $this->container->get($checkerClass);
        if (!$checker instanceof CompatibilityCheckerInterface) {
            throw new \RuntimeException(
                sprintf(
                    'Checker "%s" must implement %s',
                    $checkerClass,
                    CompatibilityCheckerInterface::class
                )
            );
        }

        return $checker;
    }
}
