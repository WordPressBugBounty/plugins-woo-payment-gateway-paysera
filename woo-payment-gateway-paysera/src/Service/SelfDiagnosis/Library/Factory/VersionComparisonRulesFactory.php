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

    public function getMinimumSupportedVersion(): string
    {
        $versions = array_keys($this->versionComparisonRules);
        sort($versions, SORT_NATURAL);
        return reset($versions);
    }

    public function getMaximumConfiguredVersion(): string
    {
        $versions = array_keys($this->versionComparisonRules);
        rsort($versions, SORT_NATURAL);
        return reset($versions);
    }

    public function getFallbackRuleForNewerVersion(): VersionComparisonRule
    {
        $maxVersion = $this->getMaximumConfiguredVersion();
        $ruleConfig = $this->versionComparisonRules[$maxVersion];

        return new VersionComparisonRule(
            $ruleConfig['gte'],
            $ruleConfig['lt']
        );
    }

    public function isVersionBelowMinimum(string $version): bool
    {
        $minVersion = $this->getMinimumSupportedVersion();
        return version_compare($version, $minVersion, '<');
    }
}
