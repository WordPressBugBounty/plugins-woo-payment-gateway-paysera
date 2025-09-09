<?php

declare(strict_types=1);

namespace Paysera\Entity;

defined('ABSPATH') || exit;

class PayseraPaths
{
    public const PAYSERA_LOGO = PayseraPluginUrl . 'assets/images/paysera.svg';
    public const PAYSERA_CHECKOUT_CSS = PayseraPluginUrl . 'assets/css/paysera-checkout.css';
    public const PAYSERA_DELIVERY_CSS = PayseraPluginUrl . 'assets/css/paysera-delivery.css';
    public const PAYSERA_DELIVERY_GRID_CSS = PayseraPluginUrl . 'assets/css/paysera-delivery-grid.css';
    public const PAYSERA_PAYMENT_CSS = PayseraPluginUrl . 'assets/css/paysera-payment.css';
    public const PAYSERA_LOGO_MENU = PayseraPluginUrl . 'assets/images/paysera-logo-menu.svg';
    public const PAYSERA_DELIVERY_BACKEND_JS = PayseraPluginUrl . 'assets/js/delivery/backend.js';
    public const PAYSERA_DELIVERY_FRONTEND_JS = PayseraPluginUrl . 'assets/js/delivery/frontend.js';
    public const PAYSERA_DELIVERY_CART_LOGOS_JS = PayseraPluginUrl . 'assets/js/delivery/cart-logos.js';
    public const PAYSERA_DELIVERY_SELECTOR_JS = PayseraPluginUrl . 'assets/js/delivery/shipping-selector.js';
    public const PAYSERA_DELIVERY_CART_VALIDATION_JS = PayseraPluginUrl . 'assets/build/paysera-cart-shipping-rates-validation.js';
    public const PAYSERA_DELIVERY_CART_VALIDATION_ASSETS = PayseraPluginBuildDir . 'assets/build/paysera-cart-shipping-rates-validation.asset.php';
    public const PAYSERA_DELIVERY_FRONTEND_AJAX_JS = PayseraPluginUrl . 'assets/js/delivery/frontend.ajax.js';
    public const PAYSERA_DELIVERY_FRONTEND_GRID_JS = PayseraPluginUrl . 'assets/js/delivery/frontend-grid.js';
    public const PAYSERA_ADMIN_SETTINGS_LINK = 'admin.php?page=paysera';
    public const PAYSERA_DOCUMENTATION_LINK = 'https://developers.paysera.com/';
    public const PAYSERA_PAYMENT_BACKEND_JS = PayseraPluginUrl . 'assets/js/payment/backend.js';
    public const PAYSERA_PAYMENT_FRONTEND_JS = PayseraPluginUrl . 'assets/js/payment/frontend.js';
    public const PAYSERA_PAYMENT_QUALITY_SIGN_JS = PayseraPluginUrl . 'assets/js/payment/sign.js';
    public const PAYSERA_SELECT_2_CSS = PayseraPluginUrl . 'assets/css/select2.min.css';
    public const PAYSERA_SELECT_2_JS = PayseraPluginUrl . 'assets/js/select2.min.js';
    public const WOOCOMMERCE_PAYSERA_SETTINGS = 'woocommerce_paysera_settings';

    public const PAYSERA_TRANSLATIONS = 'paysera';
    public const PAYSERA_MESSAGE = 'Paysera: ';
    public const PAYSERA_REST_BASE = 'paysera/v1';
    public const PAYSERA_BLOCK_INTEGRATION_CSS = PayseraPluginUrl . 'assets/build/style-index.css';
    public const PAYSERA_ADMIN_DELIVERY_SETTINGS_JS = PayseraPluginUrl . 'assets/js/delivery/delievery-settings-admin.js';
    public const PAYSERA_ADMIN_DELIVERY_SETTINGS_CSS = PayseraPluginUrl . 'assets/css/paysera-delivery-client-validation.css';
    public const PAYSERA_SHIPPING_BLOCK_JS = PayseraPluginUrl . 'assets/build/paysera-shipping-block.js';
    public const PAYSERA_SHIPPING_BLOCK_FRONTEND_JS = PayseraPluginUrl . 'assets/build/paysera-shipping-block-frontend.js';
    public const PAYSERA_ADMIN_AJAX_PHP = 'admin-ajax.php';
}
