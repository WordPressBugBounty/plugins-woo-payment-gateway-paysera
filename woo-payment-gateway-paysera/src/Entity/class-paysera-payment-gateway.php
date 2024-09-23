<?php

declare(strict_types=1);

defined('ABSPATH') || exit;

use Evp\Component\Money\Money;
use Paysera\Entity\PayseraPaths;
use Paysera\Entity\PayseraPaymentSettings;
use Paysera\Exception\PayseraPaymentException;
use Paysera\Factory\LoggerFactory;
use Paysera\Front\PayseraPaymentFrontHtml;
use Paysera\Generator\PayseraPaymentFieldGenerator;
use Paysera\Generator\PayseraPaymentRequestGenerator;
use Paysera\Helper\LogHelper;
use Paysera\Helper\PayseraPaymentLibraryHelper;
use Paysera\Provider\PayseraPaymentSettingsProvider;
use Paysera\Scoped\Paysera\CheckoutSdk\CheckoutFacadeFactory;
use Paysera\Scoped\Paysera\CheckoutSdk\Entity\PaymentCallbackValidationResponse;
use Paysera\Scoped\Paysera\CheckoutSdk\Entity\Request\PaymentCallbackValidationRequest;
use Paysera\Service\LoggerInterface;

class Paysera_Payment_Gateway extends WC_Payment_Gateway
{
    private const RESPONSE_STATUS_CONFIRMED = 1;
    private const RESPONSE_STATUS_ADDITIONAL_INFO = 3;

    private PayseraPaymentSettings $payseraPaymentSettings;
    private PayseraPaymentFieldGenerator $payseraPaymentFieldGenerator;
    private PayseraPaymentRequestGenerator $payseraPaymentRequestGenerator;
    private LoggerInterface $logger;

    public function __construct()
    {
        $this->payseraPaymentSettings = (new PayseraPaymentSettingsProvider())->getPayseraPaymentSettings();
        $this->logger = (new LoggerFactory())->createLogger(LogHelper::LOGGER_TYPE_PAYMENT);
        $this->payseraPaymentRequestGenerator = new PayseraPaymentRequestGenerator(
            $this->logger,
            $this->payseraPaymentSettings
        );
        $this->payseraPaymentFieldGenerator = new PayseraPaymentFieldGenerator(
            $this->payseraPaymentSettings,
            new PayseraPaymentFrontHtml(),
            new PayseraPaymentLibraryHelper($this->logger)
        );

        $this->id = 'paysera';
        $this->has_fields = true;
        $this->method_title = $this->payseraPaymentSettings->getTitle();
        $this->method_description = $this->payseraPaymentSettings->getDescription();
        $this->icon = apply_filters('woocommerce_paysera_icon', PayseraPaths::PAYSERA_LOGO);
        $this->title = $this->payseraPaymentSettings->getTitle();
        $this->description = $this->payseraPaymentSettings->getDescription();

        $this->init_form_fields();
        $this->init_settings();

        add_action('woocommerce_thankyou_paysera', [$this, 'processOrderAfterPayment']);
        add_action('woocommerce_api_wc_gateway_paysera', [$this, 'checkCallbackRequest']);
        add_action('woocommerce_update_options_payment_gateways_paysera', [$this, 'process_admin_options']);
    }

    public function admin_options(): void
    {
        wp_redirect('admin.php?page=paysera-payments');
    }

    public function payment_fields(): void
    {
        wp_enqueue_style('paysera-payment-css', PayseraPaths::PAYSERA_PAYMENT_CSS);
        wp_enqueue_script('paysera-payment-frontend-js', PayseraPaths::PAYSERA_PAYMENT_FRONTEND_JS, ['jquery']);

        print_r($this->payseraPaymentFieldGenerator->generatePaymentField());
    }

    public function process_payment($order_id): array
    {
        $order = wc_get_order($order_id);
        $order->add_order_note(
            __(PayseraPaths::PAYSERA_MESSAGE . 'Order checkout process is started', PayseraPaths::PAYSERA_TRANSLATIONS)
        );
        $this->updateOrderStatus($order, $this->payseraPaymentSettings->getPendingCheckoutStatus());

        wc_maybe_reduce_stock_levels($order_id);

        return [
            'result' => 'success',
            'redirect' => $this->payseraPaymentRequestGenerator->buildPaymentRequestUrl(
                $order,
                ($this->payseraPaymentSettings->isListOfPaymentsEnabled() === true)
                    ? esc_html($_REQUEST['payment']['pay_type'] ?? $_POST['pay_type'] ?? '') : '',
                $this->get_return_url($order)
            ),
        ];
    }

    public function processOrderAfterPayment($orderId): void
    {
        $order = wc_get_order($orderId);
        $currentStatus = 'wc-' . $order->get_status();

        if (
            $currentStatus === $this->payseraPaymentSettings->getPendingCheckoutStatus()
            && $currentStatus !== $this->payseraPaymentSettings->getNewOrderStatus()
        ) {
            $order->add_order_note(
                __(PayseraPaths::PAYSERA_MESSAGE . 'Customer came back to page', PayseraPaths::PAYSERA_TRANSLATIONS)
            );
            $this->updateOrderStatus($order, $this->payseraPaymentSettings->getNewOrderStatus());
        }
    }

    public function checkCallbackRequest(): void
    {
        if (!isset($_REQUEST['data'])) {
            $this->logger->error('Error while processing callback request: "data" parameter not found');
            print_r('"data" parameter not found');
            exit();
        }

        $checkoutFacade = (new CheckoutFacadeFactory())->create();
        $paymentValidationRequest = new PaymentCallbackValidationRequest(
            (int) $this->payseraPaymentSettings->getProjectId(),
            (string) $this->payseraPaymentSettings->getProjectPassword(),
            (string) $_REQUEST['data']
        );
        $paymentValidationRequest->setSs1($_REQUEST['ss1'] ?? null)
            ->setSs2($_REQUEST['ss2'] ?? null);

        try {
            $response = $checkoutFacade->getPaymentCallbackValidatedData($paymentValidationRequest);
            if ($response->getStatus() === self::RESPONSE_STATUS_CONFIRMED) {
                $order = wc_get_order($response->getOrder()->getOrderId());

                if ($this->isPaymentValid($order, $response) === true) {
                    error_log(
                        $this->formatLogMessage(
                            $order,
                            __('Payment confirmed with a callback', PayseraPaths::PAYSERA_TRANSLATIONS)
                        )
                    );

                    $order->add_order_note(
                        __(
                            PayseraPaths::PAYSERA_MESSAGE . 'Callback order payment completed',
                            PayseraPaths::PAYSERA_TRANSLATIONS
                        )
                    );
                    $this->updateOrderStatus($order, $this->payseraPaymentSettings->getPaidOrderStatus());

                    print_r('OK');
                }
            } elseif ($response->getStatus() === self::RESPONSE_STATUS_ADDITIONAL_INFO) {
                $order = wc_get_order($response->getOrder()->getOrderId());
                print_r('Expecting status 1 (Payment successful), status 3 (Additional payment information) received');
                $this->logger->error($this->formatLogMessage($order, 'Expecting status 1 (Payment successful), status 3 (Additional payment information) received'));
            }
        } catch (Throwable $exception) {
            $this->logger->error('Error while processing callback request', $exception);

            $error = get_class($exception) . ': ' . $exception->getMessage();
            if ($exception->getPrevious() instanceof \Throwable) {
                $error .= sprintf(' (%s)', $exception->getPrevious()->getMessage());
            }

            print_r($error);
        }

        exit();
    }

    /**
     * @param WC_Order $order
     * @param PaymentCallbackValidationResponse $response
     * @return bool
     * @throws PayseraPaymentException
     */
    private function isPaymentValid(WC_Order $order, PaymentCallbackValidationResponse $response): bool
    {
        $money = Money::create($order->get_total());
        if (!$money->isEqual(Money::createFromNoDelimiterAmount($response->getOrder()->getAmount(), null))) {
            throw new PayseraPaymentException(
                $this->formatLogMessage($order, __('Amounts do not match', PayseraPaths::PAYSERA_TRANSLATIONS))
            );
        }

        if ($order->get_currency() !== $response->getOrder()->getCurrency()) {
            throw new PayseraPaymentException(
                $this->formatLogMessage($order, __('Currencies do not match', PayseraPaths::PAYSERA_TRANSLATIONS))
            );
        }

        return true;
    }

    private function formatLogMessage(WC_Order $order, string $message): string
    {
        return sprintf(
            __('%s: Order %s; Amount: %s%s', PayseraPaths::PAYSERA_TRANSLATIONS),
            $message,
            $order->get_id(),
            $order->get_total(),
            $order->get_currency()
        );
    }

    private function updateOrderStatus(WC_Order $order, string $status): void
    {
        $orderStatus = str_replace('wc-', '', $status);
        $order->update_status(
            $orderStatus,
            __(
                PayseraPaths::PAYSERA_MESSAGE . 'Status changed to ',
                PayseraPaths::PAYSERA_TRANSLATIONS
            ) . $orderStatus,
            true
        );
    }

    /**
     * Check if the gateway is available for use.
     *
     * @return bool
     */
    public function is_available(): bool
    {
        return
            parent::is_available() === true
            && !empty($this->payseraPaymentSettings->getProjectId())
            && !empty($this->payseraPaymentSettings->getProjectPassword())
        ;
    }

    /**
     * Return whether or not this gateway still requires setup to function.
     *
     * When this gateway is toggled on via AJAX, if this returns true a
     * redirect will occur to the settings page instead.s
     *
     * @return bool
     */
    public function needs_setup(): bool
    {
        return
            empty($this->payseraPaymentSettings->getProjectId())
            || empty($this->payseraPaymentSettings->getProjectPassword())
        ;
    }
}
