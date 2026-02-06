<?php

declare(strict_types=1);

namespace Paysera\Action;

use Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry;
use Exception;
use Paysera\Blocks\PayseraBlock;
use Paysera\Entity\PayseraPaths;
use Paysera\Helper\SecurityHelper;
use Paysera\Provider\PayseraPaymentSettingsProvider;
use Paysera\Service\PaymentLoggerInterface;

defined('ABSPATH') || exit;

class PayseraPaymentActions
{
    private PaymentLoggerInterface $logger;
    private SecurityHelper $securityHelper;

    public function __construct(PaymentLoggerInterface $logger, SecurityHelper $securityHelper = null)
    {
        $this->logger = $logger;
        $this->securityHelper = $securityHelper ?? new SecurityHelper();
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
        $this->securityHelper->validateAdminRequest('paysera_payment_gateway_change');

        $action = $this->securityHelper->getValidatedActionParameter('change');

        if ($this->isReadyForEnabling($action)) {
            wp_redirect('admin.php?page=paysera-payments&enabled_massage=yes');
            exit();
        }

        WC()
            ->payment_gateways
            ->payment_gateways()['paysera']
            ->update_option(
                'enabled',
                $action === 'enable' ? 'yes' : 'no'
            )
        ;

        wp_redirect('admin.php?page=paysera-payments');
    }

    public function updatePaymentStatus(string $value): bool
    {
        return WC()
            ->payment_gateways
            ->payment_gateways()['paysera']
            ->update_option(
                'enabled',
                $value
            )
        ;
    }

    private function isReadyForEnabling(string $action): bool
    {
        if ($action !== 'enable') {
            return false;
        }

        $payseraPaymentSettings = (new PayseraPaymentSettingsProvider())->getPayseraPaymentSettings();

        return $payseraPaymentSettings->getProjectId() === ''
            || $payseraPaymentSettings->getProjectId() === null
            || $payseraPaymentSettings->getProjectPassword() === ''
            || $payseraPaymentSettings->getProjectPassword() === null
        ;
    }
}
