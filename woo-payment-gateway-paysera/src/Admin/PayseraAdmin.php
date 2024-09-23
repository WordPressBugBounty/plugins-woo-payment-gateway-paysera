<?php

declare(strict_types=1);

namespace Paysera\Admin;

defined('ABSPATH') || exit;

use Paysera\Entity\PayseraPaths;

class PayseraAdmin
{
    private PayseraDeliveryAdmin $payseraDeliveryAdmin;
    private PayseraPaymentAdmin $payseraPaymentAdmin;
    private PayseraAdminHtml $payseraAdminHtml;

    public function __construct(PayseraDeliveryAdmin $payseraDeliveryAdmin)
    {
        $this->payseraDeliveryAdmin = $payseraDeliveryAdmin;
        $this->payseraPaymentAdmin = new PayseraPaymentAdmin();
        $this->payseraAdminHtml = new PayseraAdminHtml();
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
        }
    }

    public function payseraAboutSubMenu(): void
    {
        printf($this->payseraAdminHtml->buildAboutPage());
    }

    public function payseraDeliverySubMenu(): void
    {
        $this->payseraDeliveryAdmin->buildSettingsPage();
    }

    public function payseraPaymentSubMenu(): void
    {
        $this->payseraPaymentAdmin->buildSettingsPage();
    }
}
