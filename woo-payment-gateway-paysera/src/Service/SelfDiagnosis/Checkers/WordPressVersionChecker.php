<?php

declare(strict_types=1);

namespace Paysera\Service\SelfDiagnosis\Checkers;

use Paysera\Service\SelfDiagnosis\Library\CompatibilityCheckerInterface;
use Paysera\Service\SelfDiagnosis\Library\Result\CheckerResult;
use Paysera\Service\SelfDiagnosis\Library\Util\SelfDiagnosisConfig;

class WordPressVersionChecker extends AbstractChecker implements CompatibilityCheckerInterface
{
    protected const CHECKER_NAME = 'WordPress Version Check';
    private const SUCCESS_MESSAGE_FORMAT = 'Your WordPress version \'%s\' meets the minimum requirement of \'%s\'.';
    private const FAILURE_MESSAGE_FORMAT = 'Your WordPress version \'%s\' is below the minimum required version of \'%s\'. Please update WordPress.';
    private const NOT_DETECTED_MESSAGE = 'WordPress is not detected. Ensure the environment is running WordPress.';

    /**
     * Runs the WordPress version compatibility check.
     *
     * @return CheckerResult
     */
    public function check(): CheckerResult
    {
        global $wp_version;
        $minimumVersion = $this->config->get('wordpress_version');

        if (!isset($wp_version)) {
            $this->result->isSuccess = false;
            $this->result->details = self::NOT_DETECTED_MESSAGE;
            return $this->result;
        }

        $isVersionCompatible = version_compare($wp_version, $minimumVersion, '>=');

        $this->result->isSuccess = $isVersionCompatible;
        $this->result->details = $isVersionCompatible
            ? sprintf(
                self::SUCCESS_MESSAGE_FORMAT,
                $wp_version,
                $minimumVersion
            )
            : sprintf(
                self::FAILURE_MESSAGE_FORMAT,
                $wp_version,
                $minimumVersion
            );

        return $this->result;
    }
}
