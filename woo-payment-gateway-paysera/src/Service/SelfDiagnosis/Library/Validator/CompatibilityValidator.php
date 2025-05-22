<?php

declare(strict_types=1);

namespace Paysera\Service\SelfDiagnosis\Library\Validator;

use Paysera\Service\SelfDiagnosis\Library\CompatibilityCheckerInterface;

class CompatibilityValidator
{
    /**
     * @var array<CompatibilityCheckerInterface>
     */
    private array $checkers;

    /**
     * @param array<CompatibilityCheckerInterface> $checkers
     */
    public function __construct(array $checkers)
    {
        $this->checkers = $checkers;
    }

    public function validate(): bool
    {
        foreach ($this->checkers as $checker) {
            if (!$checker->check()->isSuccess) {
                return false;
            }
        }

        return true;
    }
}
