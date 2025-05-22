<?php

declare(strict_types=1);

namespace Paysera\Service\SelfDiagnosis\Library\Factory;

use Paysera\Service\SelfDiagnosis\Library\Util\VersionComparisonRule;

class VersionComparisonRulesFactory
{
    private array $versionComparisonRules;

    public function __construct(array $versionComparisonRules)
    {
        $this->versionComparisonRules = $versionComparisonRules;
    }

    public function getRuleFor(string $version): ?VersionComparisonRule
    {
        foreach ($this->versionComparisonRules as $mainVersion => $ruleConfig) {
            if (strpos($version, $mainVersion) === 0) {
                return new VersionComparisonRule(
                    $ruleConfig['gte'],
                    $ruleConfig['lt']
                );
            }
        }

        return null;
    }
}
