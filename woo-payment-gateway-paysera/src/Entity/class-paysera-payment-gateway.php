<?php

declare(strict_types=1);

defined('ABSPATH') || exit;

use Evp\Component\Money\Money;
use Paysera\Entity\PayseraPaths;
use Paysera\Entity\PayseraPaymentSettings;
use Paysera\Exception\PayseraPaymentException;
use Paysera\Exception\PayseraPaymentRefundException;
use Paysera\Front\PayseraPaymentFrontHtml;
use Paysera\Generator\PayseraPaymentFieldGenerator;
use Paysera\Generator\PayseraPaymentRequestGenerator;
use Paysera\Helper\PayseraHTMLHelper;
use Paysera\Helper\PayseraPaymentLibraryHelper;
use Paysera\Provider\ContainerProvider;
use Paysera\Provider\PayseraPaymentSettingsProvider;
use Paysera\Repository\RefundRepository;
use Paysera\Scoped\Paysera\CheckoutSdk\CheckoutFacadeFactory;
use Paysera\Scoped\Paysera\CheckoutSdk\Entity\PaymentCallbackValidationResponse;
use Paysera\Scoped\Paysera\CheckoutSdk\Entity\Refund;
use Paysera\Scoped\Paysera\CheckoutSdk\Entity\Request\PaymentCallbackValidationRequest;
use Paysera\Scoped\Psr\Container\ContainerInterface;
use Paysera\Service\LoggerInterface;
use Paysera\Helper\EventHandlingHelper;
use Paysera\Entity\PayseraDeliverySettings;
use Paysera\Service\PaymentLoggerInterface;
use Paysera\Service\RefundAmountCalculator;

class Paysera_Payment_Gateway extends WC_Payment_Gateway
{
    private const RESPONSE_STATUS_CONFIRMED = 1;
    private const RESPONSE_STATUS_ADDITIONAL_INFO = 3;
    private const RESPONSE_STATUS_REFUNDED = 5;

    private ContainerInterface $container;
    private PayseraPaymentSettings $payseraPaymentSettings;
    private PayseraPaymentFieldGenerator $payseraPaymentFieldGenerator;
    private PayseraPaymentRequestGenerator $payseraPaymentRequestGenerator;
    private LoggerInterface $logger;

    public function __construct()
    {
        $this->container = (new ContainerProvider())->getContainer();
        $this->payseraPaymentSettings = $this->container
            ->get(PayseraPaymentSettingsProvider::class)
            ->getPayseraPaymentSettings()
        ;
        $this->logger = $this->container->get(PaymentLoggerInterface::class);
        $this->payseraPaymentRequestGenerator = new PayseraPaymentRequestGenerator(
            $this->logger,
            $this->payseraPaymentSettings
        );
        $this->payseraPaymentFieldGenerator = new PayseraPaymentFieldGenerator(
            $this->payseraPaymentSettings,
            new PayseraPaymentFrontHtml(),
            $this->container->get(PayseraPaymentLibraryHelper::class),
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
        add_action('woocommerce_api_wc_gateway_paysera', [$this, 'processCallbackRequest']);
        add_action('woocommerce_update_options_payment_gateways_paysera', [$this, 'process_admin_options']);
    }

    public function admin_options(): void
    {
        wp_redirect('admin.php?page=paysera-payments');
    }

    public function payment_fields(): void
    {
        PayseraHTMLHelper::enqueueCSS('paysera-payment-css', PayseraPaths::PAYSERA_PAYMENT_CSS);
        PayseraHTMLHelper::enqueueJS(
            'paysera-payment-frontend-js',
            PayseraPaths::PAYSERA_PAYMENT_FRONTEND_JS,
            ['jquery']
        );

        print_r($this->payseraPaymentFieldGenerator->generatePaymentField());
    }

    public function process_payment($order_id): array
    {
        $order = wc_get_order($order_id);
        $order->add_order_note(
            __(PayseraPaths::PAYSERA_MESSAGE . 'Order checkout process is started', PayseraPaths::PAYSERA_TRANSLATIONS)
        );
        $this->updateOrderStatus($order, $this->payseraPaymentSettings->getPendingPaymentStatus());

        wc_maybe_reduce_stock_levels($order_id);

        $payType = '';
        if ($this->payseraPaymentSettings->isListOfPaymentsEnabled()) {
            $rawPayType = $_POST['payment']['pay_type'] ?? $_POST['pay_type'] ?? '';

            $payType = sanitize_text_field($rawPayType);

            if (!preg_match('/^[a-zA-Z0-9_-]*$/', $payType)) {
                $payType = '';
            }
        }

        return [
            'result' => 'success',
            'redirect' => $this->payseraPaymentRequestGenerator->buildPaymentRequestUrl(
                $order,
                $payType,
                $this->get_return_url($order)
            ),
        ];
    }

    public function processCallbackRequest(): void
    {
        try {
            $requestData = $_REQUEST;
            $callbackResponse = $this->checkCallback($requestData);
            $order = wc_get_order($callbackResponse->getOrder()->getOrderId());
            if (!$order instanceof WC_Order) {
                throw new PayseraPaymentException('Order not found: ' . $callbackResponse->getOrder()->getOrderId());
            }

            switch ($callbackResponse->getStatus()) {
                case self::RESPONSE_STATUS_CONFIRMED:
                    $this->confirmOrder($callbackResponse, $order);
                    break;
                case self::RESPONSE_STATUS_REFUNDED:
                    $this->refundOrder($callbackResponse, $order);
                    break;
            }
            die('OK');
        } catch (PayseraPaymentException|PayseraPaymentRefundException $exception) {
            $this->logError($exception->getMessage(), $exception->getCode());
            wp_send_json_error($exception->getMessage(), $exception->getCode());
        } catch (Throwable $exception) {
            $this->logger->error($exception->getMessage(), $exception);
            wp_send_json_error('Error while processing callback', 500);
        } finally {
            exit;
        }
    }
    private function logError($message, $code = 500): void
    {
        if ($code >= 500) {
            $this->logger->error($message);
            return;
        }
        $this->logger->info($message);
    }

    private function checkCallback(array $request): PaymentCallbackValidationResponse
    {
        if (!isset($request['data'])) {
            throw new PayseraPaymentException('Error while processing callback request: "data" parameter not found', 400);
        }

        $checkoutFacade = (new CheckoutFacadeFactory())->create();

        $paymentValidationRequest = new PaymentCallbackValidationRequest(
            (int)$this->payseraPaymentSettings->getProjectId(),
            (string)$this->payseraPaymentSettings->getProjectPassword(),
            (string)$request['data']
        );
        $paymentValidationRequest->setSs1($request['ss1'] ?? null)
            ->setSs2($request['ss2'] ?? null)
            ->setSs3($request['ss3'] ?? null);

        return $checkoutFacade->getPaymentCallbackValidatedData($paymentValidationRequest);
    }

    private function confirmOrder(PaymentCallbackValidationResponse $callbackResponse, WC_Order $order): void
    {
        if (!$this->isPaymentValid($order, $callbackResponse)) {
            throw new PayseraPaymentException('Payment confirmation failed: Payment not valid');
        }
        if ($order->get_meta(PayseraPaymentSettings::ORDER_PAYMENT_CONFIRMED_META_KEY) === '1') {
            return;
        }

        $order->update_meta_data(PayseraPaymentSettings::ORDER_PAYMENT_CONFIRMED_META_KEY, '1');
        $order->update_meta_data(PayseraPaymentSettings::ORDER_PAYMENT_AMOUNT, $callbackResponse->getPaymentAmount());
        $order->update_meta_data(PayseraPaymentSettings::ORDER_PAYMENT_CURRENCY, $callbackResponse->getPaymentCurrency());
        $order->save();

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

        $eventHandlingHelper = $this->container->get(EventHandlingHelper::class);
        $eventHandlingHelper->handle(
            PayseraDeliverySettings::WC_ORDER_EVENT_PAYMENT_COMPLETED,
            [
                'order' => $order,
            ]
        );
    }

    /**
     * @throws PayseraPaymentRefundException
     */
    private function refundOrder(PaymentCallbackValidationResponse $callbackResponse, WC_Order $order): void
    {
        $order->add_order_note(
            PayseraPaths::PAYSERA_MESSAGE .
            __( 'Callback with refund initiated', PayseraPaths::PAYSERA_TRANSLATIONS)
        );

        if (!$callbackResponse->getRefund() instanceof Refund) {
            throw new PayseraPaymentRefundException('Refund not found in callback response', 422);
        }
        $refund = $callbackResponse->getRefund();

        $refundRepository = new RefundRepository();

        if ($refundRepository->refundExistsForCallback($order, $refund)) {
            throw new PayseraPaymentRefundException('Refund already exists for this callback', 422);
        };

        $refundAmountCalculator = $this->container->get(RefundAmountCalculator::class);
        $shopCalculatedRefundAmount = $refundAmountCalculator->calculateShopRefundAmount($order, $refund);

        $orderTotal = new Money($order->get_total(), $order->get_currency());
        $totalRefunded = new Money($order->get_total_refunded(), $order->get_currency());
        $moneyLeft = $orderTotal->sub($totalRefunded);

        if ($moneyLeft->isLt($shopCalculatedRefundAmount)) {
            throw new PayseraPaymentRefundException(
                __('The refund amount exceeds the available refundable balance.', PayseraPaths::PAYSERA_TRANSLATIONS),
                422
            );
        }

        $refundRepository->createRefund($order, $shopCalculatedRefundAmount, $refund, __('Refunded via Paysera Payment Gateway', PayseraPaths::PAYSERA_TRANSLATIONS));

        $totalRefunded = new Money($order->get_total_refunded(), $order->get_currency());

        if ($totalRefunded->isEqual($orderTotal)) {
            $this->updateOrderStatus($order, $this->payseraPaymentSettings->getRefundPaymentStatus());
        }

        $order->add_order_note(
            PayseraPaths::PAYSERA_MESSAGE .
            __(
                'Refunded via Paysera Payment Gateway',
                PayseraPaths::PAYSERA_TRANSLATIONS
            )
        );
    }

    public function processOrderAfterPayment($orderId): void
    {
        $order = wc_get_order($orderId);

        if ($order->get_meta(PayseraDeliverySettings::DELIVERY_CUSTOMER_BACK_TO_PAGE)) {
            return;
        }

        $order->add_order_note(
            __(PayseraPaths::PAYSERA_MESSAGE . 'Customer came back to page', PayseraPaths::PAYSERA_TRANSLATIONS)
        );

        $order->add_meta_data(PayseraDeliverySettings::DELIVERY_CUSTOMER_BACK_TO_PAGE, '1', true);
        $order->save_meta_data();
    }

    /**
     * @param WC_Order $order
     * @param PaymentCallbackValidationResponse $response
     * @return bool
     * @throws PayseraPaymentException
     */
    private function isPaymentValid(WC_Order $order, PaymentCallbackValidationResponse $response): bool
    {
        if ($order->get_currency() !== $response->getOrder()->getCurrency()) {
            throw new PayseraPaymentException(
                $this->formatLogMessage($order, __('Currencies do not match', PayseraPaths::PAYSERA_TRANSLATIONS))
            );
        }

        $orderMoney = new Money($order->get_total(), $order->get_currency());
        $responseMoney = Money::createFromNoDelimiterAmount(
            $response->getOrder()->getAmount(),
            $response->getOrder()->getCurrency()
        );

        if (!$orderMoney->isEqual($responseMoney)) {
            throw new PayseraPaymentException(
                $this->formatLogMessage($order, __('Amounts do not match', PayseraPaths::PAYSERA_TRANSLATIONS))
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
            parent::is_available()
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
