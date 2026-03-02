<?php

declare(strict_types=1);

namespace Paysera\Admin;

defined('ABSPATH') || exit;

use Paysera\Entity\PayseraPaths;
use Paysera\Helper\PayseraHTMLHelper;
use Paysera\Helper\PayseraPaymentHelper;
use Paysera\Entity\PayseraPaymentSettings;

class PayseraPaymentAdminHtml
{
    private $payseraPaymentHelper;

    public function __construct()
    {
        $this->payseraPaymentHelper = new PayseraPaymentHelper();
    }

    public function buildCheckoutSettings(string $activeTab, ?int $projectId): void
    {
        PayseraHTMLHelper::enqueueCSS('paysera-delivery-css', PayseraPaths::PAYSERA_DELIVERY_CSS);
        PayseraHTMLHelper::enqueueCSS('paysera-select-2-css', PayseraPaths::PAYSERA_SELECT_2_CSS);
        PayseraHTMLHelper::enqueueJS('paysrea-select-2-js', PayseraPaths::PAYSERA_SELECT_2_JS, ['jquery']);
        PayseraHTMLHelper::enqueueJS('paysera-payment-backend-js', PayseraPaths::PAYSERA_PAYMENT_BACKEND_JS, ['jquery']);

        printf('<form action="options.php" class="paysera-settings" method="post">');
        printf(
            '<div><img class="paysera-delivery-settings-logo" src="' . PayseraPaths::PAYSERA_LOGO
            . '" alt="paysera-logo"/></div>'
        )
        ;
        printf('<nav class="nav-tab-wrapper woo-nav-tab-wrapper">');
        printf('<a class="nav-tab ');

        if ($activeTab === PayseraPaymentAdmin::TAB_MAIN_SETTINGS) {
            printf('nav-tab-active');
        }

        printf('" href="' . $this->payseraPaymentHelper->settingsUrl() . '">');
        printf(__('Main Settings', PayseraPaths::PAYSERA_TRANSLATIONS) . '</a>');

        if ($projectId !== null && $projectId !== 0) {
            printf('<a class="nav-tab ');

            if ($activeTab === PayseraPaymentAdmin::TAB_EXTRA_SETTINGS) {
                printf('nav-tab-active');
            }

            printf(
                '" href="'
                . $this->payseraPaymentHelper->settingsUrl(['tab' => PayseraPaymentAdmin::TAB_EXTRA_SETTINGS]) . '">'
            );
            printf(__('Extra Settings', PayseraPaths::PAYSERA_TRANSLATIONS) . '</a>');
            printf('<a class="nav-tab ');

            if ($activeTab === PayseraPaymentAdmin::TAB_ORDER_STATUS) {
                printf('nav-tab-active');
            }

            printf(
                '" href="'
                . $this->payseraPaymentHelper->settingsUrl(['tab' => PayseraPaymentAdmin::TAB_ORDER_STATUS]) . '">'
            );
            printf(__('Order Status', PayseraPaths::PAYSERA_TRANSLATIONS) . '</a>');
            printf('<a class="nav-tab ');

            if ($activeTab === PayseraPaymentAdmin::TAB_PROJECT_ADDITIONS) {
                printf('nav-tab-active');
            }

            printf(
                '" href="'
                . $this->payseraPaymentHelper->settingsUrl(['tab' => PayseraPaymentAdmin::TAB_PROJECT_ADDITIONS]) . '">'
            );
            printf(__('Project Additions', PayseraPaths::PAYSERA_TRANSLATIONS) . '</a>');
        }
        printf('</nav>');

        if ($activeTab === PayseraPaymentAdmin::TAB_MAIN_SETTINGS) {
            settings_fields(PayseraPaymentSettings::MAIN_SETTINGS_NAME);
            do_settings_sections(PayseraPaymentSettings::MAIN_SETTINGS_NAME);
        } elseif ($activeTab === PayseraPaymentAdmin::TAB_EXTRA_SETTINGS) {
            settings_fields(PayseraPaymentSettings::EXTRA_SETTINGS_NAME);
            do_settings_sections(PayseraPaymentSettings::EXTRA_SETTINGS_NAME);
        } elseif ($activeTab === PayseraPaymentAdmin::TAB_ORDER_STATUS) {
            settings_fields(PayseraPaymentSettings::STATUS_SETTINGS_NAME);
            do_settings_sections(PayseraPaymentSettings::STATUS_SETTINGS_NAME);
        } elseif ($activeTab === PayseraPaymentAdmin::TAB_PROJECT_ADDITIONS) {
            settings_fields(PayseraPaymentSettings::PROJECT_ADDITIONS_SETTINGS_NAME);
            do_settings_sections(PayseraPaymentSettings::PROJECT_ADDITIONS_SETTINGS_NAME);
        }

        submit_button();

        printf('</form>');
    }

    public function enablePayseraPaymentHtml(): string
    {
        PayseraHTMLHelper::enqueueCSS('paysera-payment-css', PayseraPaths::PAYSERA_PAYMENT_CSS);

        $html = '';

        $isEnabled = filter_var(
            WC()->payment_gateways->payment_gateways()['paysera']->enabled,
            FILTER_VALIDATE_BOOLEAN
        );

        $html .= '<a href="' . wp_nonce_url(
            admin_url('admin-post.php?action=paysera_payment_gateway_change&change=enable'),
            'paysera_payment_gateway_change'
        ) . '" class="button paysera-button' . ($isEnabled ? ' paysera-button-active"' : '"') . '>'
            . __('Enable', PayseraPaths::PAYSERA_TRANSLATIONS)
        ;

        $html .= '<a href="' . wp_nonce_url(
            admin_url('admin-post.php?action=paysera_payment_gateway_change&change=disable'),
            'paysera_payment_gateway_change'
        ) . '" class="button paysera-button' . (!$isEnabled ? ' paysera-button-active"' : '"') . '>'
            . __('Disable', PayseraPaths::PAYSERA_TRANSLATIONS)
        ;

        $html .= '</a>';

        return $html;
    }
}
