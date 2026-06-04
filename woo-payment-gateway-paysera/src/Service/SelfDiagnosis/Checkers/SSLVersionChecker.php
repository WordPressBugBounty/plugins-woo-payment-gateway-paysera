<?php

declare(strict_types=1);

namespace Paysera\Service\SelfDiagnosis\Checkers;

use Paysera\Service\SelfDiagnosis\Library\CompatibilityCheckerInterface;
use Paysera\Service\SelfDiagnosis\Library\Factory\VersionComparisonRulesFactory;
use Paysera\Service\SelfDiagnosis\Library\Result\CheckerResult;
use Paysera\Service\SelfDiagnosis\Library\Util\SelfDiagnosisConfig;

class SSLVersionChecker extends AbstractChecker implements CompatibilityCheckerInterface
{
    protected const CHECKER_NAME = 'SSL Version Compatibility Check';
    private const OPENSSL_IS_NOT_INSTALLED = 'OpenSSL extension is not installed or enabled.';
    private const UNABLE_TO_DETERMINE_VERSION = 'Unable to determine OpenSSL version from: %s';
    private const FAILURE_MESSAGE_FORMAT = 'PHP %s and OpenSSL %s are NOT compatible. Expected OpenSSL >= %s and < %s for PHP %s.';
    private const SUCCESS_MESSAGE_FORMAT = 'PHP %s and OpenSSL %s are compatible.';
    private const PHP_VERSION_TOO_LOW_FORMAT = 'PHP version %s is not supported. Minimum required version is %s.';
    private const PHP_VERSION_TOO_HIGH_FORMAT = 'PHP %s and OpenSSL %s appear compatible. Note: PHP %s is not explicitly tested but uses PHP %s compatibility rules. This configuration should be verified.';

    private VersionComparisonRulesFactory $versionComparisonRulesFactory;
    private string $phpVersion;
    private ?string $opensslVersionOverride;

    public function __construct(
        SelfDiagnosisConfig $config,
        VersionComparisonRulesFactory $versionComparisonRulesFactory,
        ?string $phpVersion = null,
        ?string $opensslVersionOverride = null
    ) {
        parent::__construct($config);

        $this->versionComparisonRulesFactory = $versionComparisonRulesFactory;
        $this->phpVersion = $phpVersion ?? PHP_VERSION;
        $this->opensslVersionOverride = $opensslVersionOverride;
    }

    public function check(): CheckerResult
    {
        if ($this->opensslVersionOverride !== null) {
            $opensslVersionText = $this->opensslVersionOverride;
        } else {
            if (!defined('OPENSSL_VERSION_TEXT')) {
                $this->result->isSuccess = false;
                $this->result->details = self::OPENSSL_IS_NOT_INSTALLED;
                return $this->result;
            }

            $opensslVersionText = OPENSSL_VERSION_TEXT;
        }

        $opensslVersion = $this->parseOpenSslVersion($opensslVersionText);
        if ($opensslVersion === null) {
            $this->result->isSuccess = false;
            $this->result->details = sprintf(self::UNABLE_TO_DETERMINE_VERSION, $opensslVersionText);
            return $this->result;
        }

        $opensslCompareVersion = trim($opensslVersion, 'A..Za..z');
        $phpVersion = $this->phpVersion;

        $versionComparisonRule = $this->versionComparisonRulesFactory->getRuleFor($phpVersion);

        if ($versionComparisonRule === null) {
            return $this->handleUnknownPhpVersion($opensslVersion);
        }

        $success = $versionComparisonRule->isCompatible($opensslCompareVersion);

        if ($success) {
            $this->result->isSuccess = true;
            $this->result->details = sprintf(
                self::SUCCESS_MESSAGE_FORMAT,
                $phpVersion,
                $opensslVersion
            );

            return $this->result;
        }

        $this->result->isSuccess = false;
        $this->result->details = sprintf(
            self::FAILURE_MESSAGE_FORMAT,
            $phpVersion,
            $opensslVersion,
            $versionComparisonRule->getMinCompatibleVersion(),
            $versionComparisonRule->getVersionCompatibleBelow(),
            $phpVersion
        );

        return $this->result;
    }

    private function parseOpenSslVersion(string $opensslVersionText): ?string
    {
        if (!preg_match('/\b(\d+\.\d+\.\d\w?+)\b/', $opensslVersionText, $matches)) {
            return null;
        }

        return $matches[1];
    }

    private function handleUnknownPhpVersion(string $opensslVersion): CheckerResult
    {
        if ($this->versionComparisonRulesFactory->isVersionBelowMinimum($this->phpVersion)) {
            return $this->handlePhpVersionBelowMinimum();
        }

        return $this->handlePhpVersionAboveMaximum($opensslVersion);
    }

    private function handlePhpVersionBelowMinimum(): CheckerResult
    {
        $this->result->isSuccess = false;
        $this->result->details = sprintf(
            self::PHP_VERSION_TOO_LOW_FORMAT,
            $this->phpVersion,
            $this->versionComparisonRulesFactory->getMinimumSupportedVersion()
        );

        return $this->result;
    }

    private function handlePhpVersionAboveMaximum(string $opensslVersion): CheckerResult
    {
        $fallbackRule = $this->versionComparisonRulesFactory->getFallbackRuleForNewerVersion();
        $maxConfiguredVersion = $this->versionComparisonRulesFactory->getMaximumConfiguredVersion();

        $opensslCompareVersion = trim($opensslVersion, 'A..Za..z');
        $isCompatible = $fallbackRule->isCompatible($opensslCompareVersion);

        if ($isCompatible) {
            $this->result->isSuccess = true;
            $this->result->details = sprintf(
                self::PHP_VERSION_TOO_HIGH_FORMAT,
                $this->phpVersion,
                $opensslVersion,
                $this->phpVersion,
                $maxConfiguredVersion
            );
        } else {
            $this->result->isSuccess = false;
            $this->result->details = sprintf(
                self::FAILURE_MESSAGE_FORMAT,
                $this->phpVersion,
                $opensslVersion,
                $fallbackRule->getMinCompatibleVersion(),
                $fallbackRule->getVersionCompatibleBelow(),
                $maxConfiguredVersion
            );
        }

        return $this->result;
    }
}
