<?php

declare(strict_types=1);

namespace Paysera\Admin;

defined('ABSPATH') || exit;

use Paysera\Entity\PayseraPaths;
use Paysera\Entity\PayseraPaymentSettings;
use Paysera\Helper\LogHelper;
use Paysera\Helper\PayseraPaymentHelper;
use Paysera\Provider\PayseraPaymentSettingsProvider;
use Paysera\Service\SettingsSynchronizer;

class PayseraPaymentAdmin
{
    public const TAB_MAIN_SETTINGS = 'main_settings';
    public const TAB_EXTRA_SETTINGS = 'extra_settings';
    public const TAB_ORDER_STATUS = 'order_status';
    public const TAB_PROJECT_ADDITIONS = 'project_additions';

    private PayseraAdminHtml $adminHtml;
    private PayseraPaymentSettings $payseraPaymentSettings;
    private PayseraAdminHtml $payseraAdminHtml;
    private PayseraPaymentAdminHtml $payseraPaymentAdminHtml;
    private PayseraPaymentHelper $payseraPaymentHelper;

    private string $tab;
    private array $tabs;

    public function __construct(
        PayseraAdminHtml $adminHtml
    ) {
        $this->adminHtml = $adminHtml;
        $this->payseraPaymentSettings = (new PayseraPaymentSettingsProvider())->getPayseraPaymentSettings();
        $this->payseraAdminHtml = new PayseraAdminHtml();
        $this->payseraPaymentAdminHtml = new PayseraPaymentAdminHtml();
        $this->payseraPaymentHelper = new PayseraPaymentHelper();
        $this->tab = self::TAB_MAIN_SETTINGS;
        $this->tabs = [
            self::TAB_MAIN_SETTINGS,
            self::TAB_EXTRA_SETTINGS,
            self::TAB_ORDER_STATUS,
            self::TAB_PROJECT_ADDITIONS,
        ];
    }

    public function build()
    {
        add_action('admin_init', [$this, 'settingsInit']);
    }

    public function settingsInit(): void
    {
        if (array_key_exists('tab', $_GET) === true) {
            $this->tab = sanitize_text_field(wp_unslash($_GET['tab']));
        }

        if (in_array($this->tab, $this->tabs, true) === false) {
            $this->tab = self::TAB_MAIN_SETTINGS;
        }

        add_settings_section(
            self::TAB_MAIN_SETTINGS,
            null,
            [$this, 'payseraPaymentSettingsSectionCallback'],
            PayseraPaymentSettings::MAIN_SETTINGS_NAME
        );
        add_settings_section(
            self::TAB_EXTRA_SETTINGS,
            null,
            [$this, 'payseraPaymentSettingsSectionCallback'],
            PayseraPaymentSettings::EXTRA_SETTINGS_NAME
        );
        add_settings_section(
            self::TAB_ORDER_STATUS,
            null,
            [$this, 'payseraPaymentSettingsSectionCallback'],
            PayseraPaymentSettings::STATUS_SETTINGS_NAME
        );
        add_settings_section(
            self::TAB_PROJECT_ADDITIONS,
            null,
            [$this, 'payseraPaymentSettingsSectionCallback'],
            PayseraPaymentSettings::PROJECT_ADDITIONS_SETTINGS_NAME
        );

        register_setting(
            PayseraPaymentSettings::MAIN_SETTINGS_NAME,
            PayseraPaymentSettings::MAIN_SETTINGS_NAME,
            [
                'sanitize_callback' => function ($value) {
                    if (isset($value[PayseraPaymentSettings::TEST_MODE])) {
                        SettingsSynchronizer::syncTestMode($value[PayseraPaymentSettings::TEST_MODE]);
                    }
                    return $value;
                },
            ]
        );
        register_setting(PayseraPaymentSettings::EXTRA_SETTINGS_NAME, PayseraPaymentSettings::EXTRA_SETTINGS_NAME);
        register_setting(PayseraPaymentSettings::STATUS_SETTINGS_NAME, PayseraPaymentSettings::STATUS_SETTINGS_NAME);
        register_setting(
            PayseraPaymentSettings::PROJECT_ADDITIONS_SETTINGS_NAME,
            PayseraPaymentSettings::PROJECT_ADDITIONS_SETTINGS_NAME
        );

        if ($this->tab === self::TAB_MAIN_SETTINGS) {
            add_settings_field(
                PayseraPaymentSettings::ENABLED,
                __('Enable', PayseraPaths::PAYSERA_TRANSLATIONS),
                [$this, 'enableRender'],
                PayseraPaymentSettings::MAIN_SETTINGS_NAME,
                self::TAB_MAIN_SETTINGS
            );
            add_settings_field(
                PayseraPaymentSettings::PROJECT_ID,
                __('Project ID', PayseraPaths::PAYSERA_TRANSLATIONS),
                [$this, 'projectIdRender'],
                PayseraPaymentSettings::MAIN_SETTINGS_NAME,
                self::TAB_MAIN_SETTINGS
            );
            add_settings_field(
                PayseraPaymentSettings::PROJECT_PASSWORD,
                __('Project password', PayseraPaths::PAYSERA_TRANSLATIONS),
                [$this, 'projectPasswordRender'],
                PayseraPaymentSettings::MAIN_SETTINGS_NAME,
                self::TAB_MAIN_SETTINGS
            );
            add_settings_field(
                PayseraPaymentSettings::TEST_MODE,
                __('Test mode', PayseraPaths::PAYSERA_TRANSLATIONS),
                [$this, 'testModeRender'],
                PayseraPaymentSettings::MAIN_SETTINGS_NAME,
                self::TAB_MAIN_SETTINGS
            );
        } elseif ($this->tab === self::TAB_EXTRA_SETTINGS) {
            add_settings_field(
                PayseraPaymentSettings::TITLE,
                __('Title', PayseraPaths::PAYSERA_TRANSLATIONS),
                [$this, 'titleRender'],
                PayseraPaymentSettings::EXTRA_SETTINGS_NAME,
                self::TAB_EXTRA_SETTINGS
            );
            add_settings_field(
                PayseraPaymentSettings::DESCRIPTION,
                __('Description', PayseraPaths::PAYSERA_TRANSLATIONS),
                [$this, 'descriptionRender'],
                PayseraPaymentSettings::EXTRA_SETTINGS_NAME,
                self::TAB_EXTRA_SETTINGS
            );
            add_settings_field(
                PayseraPaymentSettings::LIST_OF_PAYMENTS,
                __('List of payments', PayseraPaths::PAYSERA_TRANSLATIONS),
                [$this, 'listOfPaymentsRender'],
                PayseraPaymentSettings::EXTRA_SETTINGS_NAME,
                self::TAB_EXTRA_SETTINGS
            );
            add_settings_field(
                PayseraPaymentSettings::SPECIFIC_COUNTRIES,
                __('Specific countries', PayseraPaths::PAYSERA_TRANSLATIONS),
                [$this, 'specificCountriesRender'],
                PayseraPaymentSettings::EXTRA_SETTINGS_NAME,
                self::TAB_EXTRA_SETTINGS
            );
            add_settings_field(
                PayseraPaymentSettings::GRID_VIEW,
                __('Grid view', PayseraPaths::PAYSERA_TRANSLATIONS),
                [$this, 'gridViewRender'],
                PayseraPaymentSettings::EXTRA_SETTINGS_NAME,
                self::TAB_EXTRA_SETTINGS
            );
            add_settings_field(
                PayseraPaymentSettings::BUYER_CONSENT,
                __('Buyer consent', PayseraPaths::PAYSERA_TRANSLATIONS),
                [$this, 'buyerConsentRender'],
                PayseraPaymentSettings::EXTRA_SETTINGS_NAME,
                self::TAB_EXTRA_SETTINGS
            );
            add_settings_field(
                PayseraPaymentSettings::LOG_LEVEL,
                __('Log Level', PayseraPaths::PAYSERA_TRANSLATIONS),
                [$this, 'logLevelOptionsRender'],
                PayseraPaymentSettings::EXTRA_SETTINGS_NAME,
                self::TAB_EXTRA_SETTINGS
            );
        } elseif ($this->tab === self::TAB_ORDER_STATUS) {
            add_settings_field(
                PayseraPaymentSettings::PENDING_PAYMENT_STATUS,
                __('Pending Checkout', PayseraPaths::PAYSERA_TRANSLATIONS),
                [$this, 'pendingCheckoutRender'],
                PayseraPaymentSettings::STATUS_SETTINGS_NAME,
                self::TAB_ORDER_STATUS
            );
            add_settings_field(
                PayseraPaymentSettings::PAID_ORDER_STATUS,
                __('Paid Order Status', PayseraPaths::PAYSERA_TRANSLATIONS),
                [$this, 'paidOrderStatusRender'],
                PayseraPaymentSettings::STATUS_SETTINGS_NAME,
                self::TAB_ORDER_STATUS
            );
            add_settings_field(
                PayseraPaymentSettings::REFUND_PAYMENT_STATUS,
                __('Refunded Order Status', PayseraPaths::PAYSERA_TRANSLATIONS),
                [$this, 'refundPaymentRender'],
                PayseraPaymentSettings::STATUS_SETTINGS_NAME,
                self::TAB_ORDER_STATUS
            );
        } elseif ($this->tab === self::TAB_PROJECT_ADDITIONS) {
            add_settings_field(
                PayseraPaymentSettings::OWNERSHIP_CODE_ENABLED,
                __('Ownership code', PayseraPaths::PAYSERA_TRANSLATIONS),
                [$this, 'enableOwnershipCodeRender'],
                PayseraPaymentSettings::PROJECT_ADDITIONS_SETTINGS_NAME,
                self::TAB_PROJECT_ADDITIONS
            );
            add_settings_field(
                PayseraPaymentSettings::OWNERSHIP_CODE,
                __('Write your ownership code', PayseraPaths::PAYSERA_TRANSLATIONS),
                [$this, 'ownershipCodeRender'],
                PayseraPaymentSettings::PROJECT_ADDITIONS_SETTINGS_NAME,
                self::TAB_PROJECT_ADDITIONS
            );
            add_settings_field(
                PayseraPaymentSettings::QUALITY_SIGN_ENABLED,
                __('Quality sign', PayseraPaths::PAYSERA_TRANSLATIONS),
                [$this, 'qualitySignRender'],
                PayseraPaymentSettings::PROJECT_ADDITIONS_SETTINGS_NAME,
                self::TAB_PROJECT_ADDITIONS
            );
        }
    }

    public function buildSettingsPage(): void
    {
        if (isset($_REQUEST['settings-updated'])) {
            printf($this->payseraAdminHtml->getSettingsSavedMessage());
        }

        if (
            (empty($this->payseraPaymentSettings->getProjectId()) || empty($this->payseraPaymentSettings->getProjectPassword()))
            && isset($_REQUEST['enabled_massage'])
            && sanitize_text_field(wp_unslash($_REQUEST['enabled_massage'])) === 'yes'
        ) {
            printf($this->payseraAdminHtml->getSettingsWarningNotice());
        }

        if (
            !isset($_REQUEST['settings-updated'])
            && isset($_REQUEST['compatibility-check-failed'])
            && sanitize_text_field(wp_unslash($_REQUEST['compatibility-check-failed'])) === 'yes'
        ) {
            printf($this->adminHtml->getSettingsCompatibilityValidationErrorNotice());
        }

        $this->payseraPaymentAdminHtml->buildCheckoutSettings(
            $_GET['tab'] ?? $this->tab,
            $this->payseraPaymentSettings->getProjectId()
        );
    }

    public function payseraPaymentSettingsSectionCallback(): void
    {
    }

    public function enableRender(): void
    {
        printf($this->payseraPaymentAdminHtml->enablePayseraPaymentHtml());
    }

    public function projectIdRender(): void
    {
        printf(
            $this->payseraAdminHtml->getNumberInput(true),
            esc_attr(PayseraPaymentSettings::MAIN_SETTINGS_NAME . '[' . PayseraPaymentSettings::PROJECT_ID . ']'),
            esc_attr($this->payseraPaymentSettings->getProjectId()),
            0
        );
    }

    public function projectPasswordRender(): void
    {
        printf(
            $this->payseraAdminHtml->getTextInput(true),
            esc_attr(PayseraPaymentSettings::MAIN_SETTINGS_NAME . '[' . PayseraPaymentSettings::PROJECT_PASSWORD . ']'),
            esc_attr($this->payseraPaymentSettings->getProjectPassword())
        );
    }

    public function testModeRender(): void
    {
        printf(
            $this->payseraAdminHtml->getEnableInput(
                PayseraPaymentSettings::MAIN_SETTINGS_NAME . '[' . PayseraPaymentSettings::TEST_MODE . ']',
                $this->payseraPaymentSettings->isTestModeEnabled() ? 'yes' : 'no'
            ) .
            $this->payseraAdminHtml->buildLabel(
                __('A test delivery order and test payment will be created.', PayseraPaths::PAYSERA_TRANSLATIONS)
            )
        );
    }

    public function titleRender(): void
    {
        printf(
            $this->payseraAdminHtml->getTextInput(),
            esc_attr(PayseraPaymentSettings::EXTRA_SETTINGS_NAME . '[' . PayseraPaymentSettings::TITLE . ']'),
            esc_attr($this->payseraPaymentSettings->getTitle())
        );
    }

    public function descriptionRender(): void
    {
        printf(
            $this->payseraAdminHtml->getTextAreaInput(),
            esc_attr(PayseraPaymentSettings::EXTRA_SETTINGS_NAME . '[' . PayseraPaymentSettings::DESCRIPTION . ']'),
            esc_attr($this->payseraPaymentSettings->getDescription())
        );
    }

    public function listOfPaymentsRender(): void
    {
        printf(
            $this->payseraAdminHtml->getEnableInput(
                PayseraPaymentSettings::EXTRA_SETTINGS_NAME . '[' . PayseraPaymentSettings::LIST_OF_PAYMENTS . ']',
                $this->payseraPaymentSettings->isListOfPaymentsEnabled() ? 'yes' : 'no'
            )
        );
    }

    public function specificCountriesRender(): void
    {
        $specificCountries = $this->payseraPaymentSettings->getSpecificCountries() ?? [];

        printf(
            $this->payseraAdminHtml->getMultipleSelectInput(
                $this->payseraPaymentHelper->getWooCommerceCountries(),
                $specificCountries
            ) .
            $this->payseraAdminHtml->buildLabel(
                __(
                    'All countries that are entered here and are not part of main Paysera countries list will be shown as Other countries in checkout',
                    PayseraPaths::PAYSERA_TRANSLATIONS
                )
            ),
            esc_attr(
                PayseraPaymentSettings::EXTRA_SETTINGS_NAME . '[' . PayseraPaymentSettings::SPECIFIC_COUNTRIES . ']'
            )
        );
    }

    public function gridViewRender(): void
    {
        printf(
            $this->payseraAdminHtml->getEnableInput(
                PayseraPaymentSettings::EXTRA_SETTINGS_NAME . '[' . PayseraPaymentSettings::GRID_VIEW . ']',
                $this->payseraPaymentSettings->isGridViewEnabled() ? 'yes' : 'no'
            )
        );
    }

    public function buyerConsentRender(): void
    {
        printf(
            $this->payseraAdminHtml->getEnableInput(
                PayseraPaymentSettings::EXTRA_SETTINGS_NAME . '[' . PayseraPaymentSettings::BUYER_CONSENT . ']',
                $this->payseraPaymentSettings->isBuyerConsentEnabled() ? 'yes' : 'no'
            )
        );
    }

    public function logLevelOptionsRender(): void
    {
        $logHelper = new LogHelper();

        printf(
            $this->payseraAdminHtml->getLogLevelHtml(
                PayseraPaymentSettings::EXTRA_SETTINGS_NAME . '[' . PayseraPaymentSettings::LOG_LEVEL . ']',
                $this->payseraPaymentSettings->getLogLevel(),
                LogHelper::LOGGER_TYPE_PAYMENT,
                $logHelper
            )
        );
    }

    public function paidOrderStatusRender(): void
    {
        printf(
            $this->payseraAdminHtml->getSelectInput(
                $this->payseraPaymentHelper->getWooCommerceOrderStatuses(),
                $this->payseraPaymentSettings->getPaidOrderStatus()
            ),
            esc_attr(
                PayseraPaymentSettings::STATUS_SETTINGS_NAME . '[' . PayseraPaymentSettings::PAID_ORDER_STATUS . ']'
            )
        );
    }

    public function refundPaymentRender(): void
    {
        printf(
            $this->payseraAdminHtml->getSelectInput(
                $this->payseraPaymentHelper->getWooCommerceOrderStatuses(),
                $this->payseraPaymentSettings->getRefundPaymentStatus()
            ),
            esc_attr(
                PayseraPaymentSettings::STATUS_SETTINGS_NAME . '[' . PayseraPaymentSettings::REFUND_PAYMENT_STATUS . ']'
            )
        );
    }

    public function pendingCheckoutRender(): void
    {
        printf(
            $this->payseraAdminHtml->getSelectInput(
                $this->payseraPaymentHelper->getWooCommerceOrderStatuses(),
                $this->payseraPaymentSettings->getPendingPaymentStatus()
            ),
            esc_attr(
                PayseraPaymentSettings::STATUS_SETTINGS_NAME . '['
                . PayseraPaymentSettings::PENDING_PAYMENT_STATUS . ']'
            )
        );
    }

    public function enableOwnershipCodeRender(): void
    {
        printf(
            $this->payseraAdminHtml->getEnableInput(
                PayseraPaymentSettings::PROJECT_ADDITIONS_SETTINGS_NAME . '['
                . PayseraPaymentSettings::OWNERSHIP_CODE_ENABLED . ']',
                $this->payseraPaymentSettings->isOwnershipCodeEnabled() ? 'yes' : 'no'
            )
        );
    }

    public function ownershipCodeRender(): void
    {
        printf(
            $this->payseraAdminHtml->getTextInput(),
            esc_attr(
                PayseraPaymentSettings::PROJECT_ADDITIONS_SETTINGS_NAME . '['
                . PayseraPaymentSettings::OWNERSHIP_CODE . ']'
            ),
            esc_attr($this->payseraPaymentSettings->getOwnershipCode())
        );
    }

    public function qualitySignRender(): void
    {
        printf(
            $this->payseraAdminHtml->getEnableInput(
                PayseraPaymentSettings::PROJECT_ADDITIONS_SETTINGS_NAME . '['
                . PayseraPaymentSettings::QUALITY_SIGN_ENABLED . ']',
                $this->payseraPaymentSettings->isQualitySignEnabled() ? 'yes' : 'no'
            )
        );
    }
}
