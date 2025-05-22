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

    private VersionComparisonRulesFactory $versionComparisonRulesFactory;

    public function __construct(
        SelfDiagnosisConfig $config,
        VersionComparisonRulesFactory $versionComparisonRulesFactory
    ) {
        parent::__construct($config);

        $this->versionComparisonRulesFactory = $versionComparisonRulesFactory;
    }

    /**
     * Runs the SSL version compatibility check.
     *
     * @return CheckerResult
     */
    public function check(): CheckerResult
    {
        if (!defined('OPENSSL_VERSION_TEXT')) {
            $this->result->isSuccess = false;
            $this->result->details = self::OPENSSL_IS_NOT_INSTALLED;
            return $this->result;
        }

        $opensslVersionText = OPENSSL_VERSION_TEXT;
        if (!preg_match('/\b(\d+\.\d+\.\d\w?+)\b/', $opensslVersionText, $matches)) {
            $this->result->isSuccess = false;
            $this->result->details = sprintf(self::UNABLE_TO_DETERMINE_VERSION, $opensslVersionText);
            return $this->result;
        }

        $opensslVersion = $matches[1];
        $opensslCompareVersion = trim($opensslVersion, 'A..Za..z');
        $phpVersion = PHP_VERSION;

        $versionComparisonRule = $this->versionComparisonRulesFactory->getRuleFor($phpVersion);
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
}
