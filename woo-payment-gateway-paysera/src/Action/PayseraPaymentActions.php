<?php

declare(strict_types=1);

namespace Paysera\Action;

use Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry;
use Exception;
use Paysera\Blocks\PayseraBlock;
use Paysera\Provider\PayseraPaymentSettingsProvider;
use Paysera\Service\PaymentLoggerInterface;

defined('ABSPATH') || exit;

class PayseraPaymentActions
{
    private PaymentLoggerInterface $logger;

    public function __construct(PaymentLoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function build(): void
    {
        add_action('admin_post_paysera_payment_gateway_change', [$this, 'changePaymentGatewayStatus']);
        add_action('woocommerce_blocks_loaded', [$this, 'payseraGatewayWoocommerceBlockSupport']);
    }

    public function payseraGatewayWoocommerceBlockSupport(): void
    {
        if (class_exists('Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType')) {
            add_action(
                'woocommerce_blocks_payment_method_type_registration',
                [$this, 'registerPayseraPaymentMethod'],
                5
            );
        }
    }

    public function registerPayseraPaymentMethod(PaymentMethodRegistry $paymentMethodRegistry): void
    {
        if (!class_exists('Automattic\WooCommerce\Blocks\Package')) {
            return;
        }

        try {
            $container = \Automattic\WooCommerce\Blocks\Package::container();

            $container->register(
                PayseraBlock::class,
                function () {
                    return new PayseraBlock();
                }
            );
            $paymentMethodRegistry->register(
                $container->get(PayseraBlock::class)
            );
        } catch (Exception $exception) {
            $this->logger->error('Error while registering Paysera payment method', $exception);
            die($exception->getMessage());
        }
    }

    public function changePaymentGatewayStatus(): void
    {
        if ($this->isReadyForEnabling()) {
            wp_redirect('admin.php?page=paysera-payments&enabled_massage=yes');
            exit();
        }

        WC()->payment_gateways->payment_gateways()['paysera']->update_option(
            'enabled',
            sanitize_text_field(wp_unslash($_GET['change'])) === 'enable' ? 'yes' : 'no'
        );

        wp_redirect('admin.php?page=paysera-payments');
    }

    public function updatePaymentStatus(string $value): bool
    {
        return WC()->payment_gateways->payment_gateways()['paysera']->update_option(
            'enabled',
            $value
        );
    }

    private function isReadyForEnabling(): bool
    {
        $payseraPaymentSettings = (new PayseraPaymentSettingsProvider())->getPayseraPaymentSettings();

        return (
            (empty($payseraPaymentSettings->getProjectId()) || empty($payseraPaymentSettings->getProjectPassword()))
            && isset($_GET['change'])
            && sanitize_text_field(wp_unslash($_GET['change'])) === 'enable'
        );
    }
}
