<?php

declare(strict_types=1);

namespace Paysera\Action;

use Paysera\Service\SelfDiagnosis\Library\Util\DiagnosticReportGenerator;
use Paysera\Service\SelfDiagnosis\Library\Util\ResponseHeadersUtil;
use Paysera\Entity\PayseraDeliverySettings;
use Paysera\Entity\PayseraPaths;
use Paysera\Entity\PayseraPaymentSettings;
use Paysera\Service\SelfDiagnosis\Library\Validator\CompatibilityValidator;

class PayseraSelfDiagnosisActions
{
    public const DOWNLOAD_DIAGNOSTIC_TEXT_RESULT = 'paysera_download_diagnostic_text_result';
    public const DIAGNOSTIC_TEXT_RESULT_FILENAME_FORMAT = 'diagnostic-result-%s.txt';

    public const VALUE_ENABLED = 'yes';
    public const COMPATIBILITY_CHECK_FAILED_KEY = 'compatibility-check-failed';
    private const DEFAULT_REFERER_PAGE_SLUG = 'paysera-payments';

    private DiagnosticReportGenerator $diagnosticReportGenerator;
    private ResponseHeadersUtil $responseHeadersUtil;
    private CompatibilityValidator $compatibilityValidator;

    public function __construct(
        DiagnosticReportGenerator $diagnosticReportGenerator,
        ResponseHeadersUtil $responseHeadersUtil,
        CompatibilityValidator $compatibilityValidator
    ) {
        $this->diagnosticReportGenerator = $diagnosticReportGenerator;
        $this->compatibilityValidator = $compatibilityValidator;
        $this->responseHeadersUtil = $responseHeadersUtil;
    }

    public function build(): void
    {
        add_action(sprintf('admin_post_%s', self::DOWNLOAD_DIAGNOSTIC_TEXT_RESULT), [$this, 'downloadDiagnosticTextResult']);

        add_action('add_option', [$this, 'validateCompatibility'], 10, 3);
        add_action('update_option', [$this, 'validateCompatibilityUpdateOption'], 10, 3);
    }

    public function downloadDiagnosticTextResult(): void
    {
        if(!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        $result = $this->diagnosticReportGenerator->getReport();
        $filename = sprintf(self::DIAGNOSTIC_TEXT_RESULT_FILENAME_FORMAT, date('Y-m-d_H-i-s'));

        $this->responseHeadersUtil->addTextFileDownloadHeaders($filename);
        echo $result;
        $this->responseHeadersUtil->terminateRequest();
    }

    public function validateCompatibilityUpdateOption(string $option, $oldValue, $value): void
    {
        $this->validateCompatibility($option, $value);
    }

    public function validateCompatibility(string $option, $value): void
    {
        if (
            $this->skipPaymentsCompatibilityCheck($option, $value)
            &&  $this->skipDeliveryCompatibilityCheck($option, $value)
        ) {
            return;
        }

        if ($this->compatibilityValidator->validate()) {
            return;
        }

        $this->responseHeadersUtil->wpSafeRedirect(
            sprintf(
                'admin.php?page=%s&%s=%s',
                $this->getRefererPageSlug(),
                self::COMPATIBILITY_CHECK_FAILED_KEY,
                self::VALUE_ENABLED
            )
        );

        $this->responseHeadersUtil->terminateRequest();
    }

    private function skipPaymentsCompatibilityCheck(string $option, $value): bool
    {
        return $this->isEnablingPluginFeature(
            $option,
            $value,
            PayseraPaths::WOOCOMMERCE_PAYSERA_SETTINGS,
            PayseraPaymentSettings::ENABLED
        );
    }

    private function skipDeliveryCompatibilityCheck(string $option, $value): bool
    {
        return $this->isEnablingPluginFeature(
            $option,
            $value,
            PayseraDeliverySettings::SETTINGS_NAME,
            PayseraDeliverySettings::ENABLED,
        );
    }

    private function isEnablingPluginFeature(
        string $option,
               $value,
        string $settingName,
        string $enableSettingKey
    ): bool {
        return  $option !== $settingName
            || (
                isset($value[$enableSettingKey]) === false
                || $value[$enableSettingKey] !== self::VALUE_ENABLED
            )
            || (
                isset($_REQUEST[self::COMPATIBILITY_CHECK_FAILED_KEY])
                && $_REQUEST[self::COMPATIBILITY_CHECK_FAILED_KEY] === self::VALUE_ENABLED
            );
    }

    private function getRefererPageSlug(): string
    {
        $refererQueryParams = [];
        if (isset($_SERVER['HTTP_REFERER'])) {
            parse_str(parse_url($_SERVER['HTTP_REFERER'], PHP_URL_QUERY), $refererQueryParams);
        }

        return $refererQueryParams['page'] ?? self::DEFAULT_REFERER_PAGE_SLUG;
    }
}
