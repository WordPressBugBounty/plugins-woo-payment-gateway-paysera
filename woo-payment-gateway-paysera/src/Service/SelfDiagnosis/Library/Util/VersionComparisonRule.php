<?php

declare(strict_types=1);

namespace Paysera\Service\SelfDiagnosis\Library\Util;

class VersionComparisonRule
{
    public string $minCompatibleVersion;
    public string $versionCompatibleBelow;

    public function __construct(string $minCompatibleVersion, string $versionCompatibleBelow)
    {
        $this->minCompatibleVersion = $minCompatibleVersion;
        $this->versionCompatibleBelow = $versionCompatibleBelow;
    }

    public function isCompatible(string $version): bool
    {
        return version_compare($version, $this->minCompatibleVersion, '>=') &&
            version_compare($version, $this->versionCompatibleBelow, '<');
    }

    public function getMinCompatibleVersion(): string
    {
        return $this->minCompatibleVersion;
    }

    public function getVersionCompatibleBelow(): string
    {
        return $this->versionCompatibleBelow;
    }
}
