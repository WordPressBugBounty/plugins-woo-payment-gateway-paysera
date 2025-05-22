<?php

declare(strict_types=1);

namespace Paysera\Admin;

defined('ABSPATH') || exit;

use Paysera\Entity\PayseraPaths;

class PayseraAdmin
{
    private PayseraDeliveryAdmin $deliveryAdmin;
    private PayseraPaymentAdmin $paymentAdmin;
    private PayseraSelfDiagnosticAdmin $selfDiagnosticAdmin;
    private PayseraAdminHtml $adminHtml;

    public function __construct(
        PayseraDeliveryAdmin $deliveryAdmin,
        PayseraPaymentAdmin $paymentAdmin,
        PayseraSelfDiagnosticAdmin $selfDiagnosticAdmin,
        PayseraAdminHtml $adminHtml
    ) {
        $this->deliveryAdmin = $deliveryAdmin;
        $this->paymentAdmin = $paymentAdmin;
        $this->selfDiagnosticAdmin = $selfDiagnosticAdmin;
        $this->adminHtml = $adminHtml;
    }

    public function build(): void
    {
        add_action('admin_menu', [$this, 'payseraAdminMenu']);
    }

    public function payseraAdminMenu(): void
    {
        if (class_exists('woocommerce') === true) {
            add_menu_page(
                'Paysera',
                'Paysera',
                'manage_options',
                'paysera',
                [$this, 'payseraAboutSubMenu'],
                PayseraPaths::PAYSERA_LOGO_MENU,
                58
            );

            add_submenu_page(
                'paysera',
                'About',
                __('About', PayseraPaths::PAYSERA_TRANSLATIONS),
                'manage_options',
                'paysera',
                [$this, 'payseraAboutSubMenu']
            );
            add_submenu_page(
                'paysera',
                'Delivery',
                __('Delivery', PayseraPaths::PAYSERA_TRANSLATIONS),
                'manage_options',
                'paysera-delivery',
                [$this, 'payseraDeliverySubMenu']
            );
            add_submenu_page(
                'paysera',
                'Payments',
                __('Payments', PayseraPaths::PAYSERA_TRANSLATIONS),
                'manage_options',
                'paysera-payments',
                [$this, 'payseraPaymentSubMenu']
            );
            add_submenu_page(
                'paysera',
                __('Self-Diagnosis Tool', PayseraPaths::PAYSERA_TRANSLATIONS),
                __('Diagnostic', PayseraPaths::PAYSERA_TRANSLATIONS),
                'manage_options',
                'paysera-self-diagnosis',
                [$this, 'payseraSelfDiagnosisSubMenu']
            );
        }
    }

    public function payseraAboutSubMenu(): void
    {
        printf($this->adminHtml->buildAboutPage());
    }

    public function payseraDeliverySubMenu(): void
    {
        $this->deliveryAdmin->buildSettingsPage();
    }

    public function payseraPaymentSubMenu(): void
    {
        $this->paymentAdmin->buildSettingsPage();
    }

    public function payseraSelfDiagnosisSubMenu(): void
    {
        $this->selfDiagnosticAdmin->buildDiagnosticPage();
    }
}
