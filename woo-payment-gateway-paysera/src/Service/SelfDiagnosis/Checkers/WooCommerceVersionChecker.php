<?php

declare(strict_types=1);

namespace Paysera\Service\SelfDiagnosis\Checkers;

use Paysera\Service\SelfDiagnosis\Library\CompatibilityCheckerInterface;
use Paysera\Service\SelfDiagnosis\Library\Result\CheckerResult;

class WooCommerceVersionChecker extends AbstractChecker implements CompatibilityCheckerInterface
{
    protected const CHECKER_NAME = 'WooCommerce Version Check';
    private const NOT_DETECTED_MESSAGE = 'WooCommerce is not detected. Ensure the WooCommerce plugin is installed and active.';
    private const SUCCESS_MESSAGE_FORMAT = 'Your WooCommerce version \'%s\' meets the minimum requirement of \'%s\'.';
    private const FAILURE_MESSAGE_FORMAT = 'Your WooCommerce version \'%s\' is below the minimum required version of \'%s\'. Please update WooCommerce.';

    /**
     * Runs the WooCommerce version compatibility check.
     *
     * @return CheckerResult
     */
    public function check(): CheckerResult
    {
        if (!defined('WC_VERSION')) {
            return $this->getWooCommerceNotDetectedResult();
        }

        $currentVersion = WC_VERSION;
        $minimumVersion = $this->config->get('woocommerce_version');

        $isVersionCompatible = version_compare($currentVersion, $minimumVersion, '>=');

        $this->result->isSuccess = $isVersionCompatible;
        $this->result->details = $isVersionCompatible
            ? sprintf(
                self::SUCCESS_MESSAGE_FORMAT,
                $currentVersion,
                $minimumVersion
            )
            : sprintf(
                self::FAILURE_MESSAGE_FORMAT,
                $currentVersion,
                $minimumVersion
            );

        return $this->result;
    }

    /**
     * Handles cases where WooCommerce is not detected.
     *
     * @return CheckerResult
     */
    private function getWooCommerceNotDetectedResult(): CheckerResult
    {
        $this->result->isSuccess = false;
        $this->result->details = self::NOT_DETECTED_MESSAGE;

        return $this->result;
    }
}
