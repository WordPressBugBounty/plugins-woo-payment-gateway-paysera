<?php

declare(strict_types=1);

namespace Paysera\Service\SelfDiagnosis\Library;

interface CompatibilityCheckerRegistryInterface
{
    /**
     * Retrieves all registered checkers.
     *
     * @return CompatibilityCheckerInterface[][]
     */
    public function getCheckers(): array;
}
