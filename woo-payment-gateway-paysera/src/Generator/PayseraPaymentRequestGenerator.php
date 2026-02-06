<?php

declare(strict_types=1);

namespace Paysera\Generator;

defined('ABSPATH') || exit;

use Evp\Component\Money\Money;
use Paysera\Entity\PayseraPaymentSettings;
use Paysera\Scoped\Paysera\CheckoutSdk\CheckoutFacadeFactory;
use Paysera\Scoped\Paysera\CheckoutSdk\Entity\Order;
use Paysera\Scoped\Paysera\CheckoutSdk\Entity\Request\PaymentRedirectRequest;
use Paysera\Scoped\Paysera\CheckoutSdk\Exception\ProviderException;
use Paysera\Service\LoggerInterface;
use WC_Order;

class PayseraPaymentRequestGenerator
{
    private const COUNTRY_CODE_SERBIA = 'RS';
    private const COUNTRY_CODE_KOSOVO = 'XK';
    private const STATE_CODE_KOSOVO = ['RS29', 'RS28', 'RS25', 'RSKM'];

    private PayseraPaymentSettings $payseraPaymentSettings;
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger, PayseraPaymentSettings $payseraPaymentSettings)
    {
        $this->logger = $logger;
        $this->payseraPaymentSettings = $payseraPaymentSettings;
    }

    public function buildPaymentRequestUrl(WC_Order $wcOrder, string $paymentMethod, string $acceptUrl): string
    {
        global $wp_version;

        $checkoutFacade = (new CheckoutFacadeFactory())->create();

        $orderTotal = new Money($wcOrder->get_total(), $wcOrder->get_currency());
        $amountInMinorUnits = $orderTotal->getAmountInMinorUnits();

        $order = new Order(
            $this->limitIntLength($wcOrder->get_id(), 40),
            $this->limitIntLength($amountInMinorUnits, 11),
            $this->limitStringLength($wcOrder->get_currency(), 3)
        );
        $order->setPayerFirstName($this->limitStringLength($wcOrder->get_billing_first_name()))
            ->setPayerLastName($this->limitStringLength($wcOrder->get_billing_last_name()))
            ->setPayerEmail($this->limitStringLength($wcOrder->get_billing_email()))
            ->setPayerStreet($this->limitStringLength($wcOrder->get_billing_address_1()))
            ->setPayerCity($this->limitStringLength($wcOrder->get_billing_city()))
            ->setPayerState($this->limitStringLength($wcOrder->get_billing_state(), 20))
            ->setPayerZip($this->limitStringLength($wcOrder->get_billing_postcode(), 20));

        if ($wcOrder->get_billing_country() !== '' && $wcOrder->get_billing_country() !== null) {
            $order->setPayerCountryCode($this->limitStringLength($wcOrder->get_billing_country(), 2));
        }

        $redirectRequest = new PaymentRedirectRequest(
            $this->limitIntLength($this->payseraPaymentSettings->getProjectId(), 11),
            $this->limitStringLength($this->payseraPaymentSettings->getProjectPassword()),
            $this->limitStringLength($acceptUrl),
            $this->limitStringLength(htmlspecialchars_decode($wcOrder->get_cancel_order_url())),
            $this->limitStringLength(trailingslashit(get_home_url()) . '?wc-api=wc_gateway_paysera'),
            $order
        );
        $redirectRequest->setCountry($this->limitStringLength($wcOrder->get_billing_country(), 2))
            ->setPayment($this->limitStringLength($paymentMethod, 20))
            ->setLanguage($this->limitStringLength($this->getLanguage(), 3))
            ->setTest($this->payseraPaymentSettings->isTestModeEnabled())
            ->setBuyerConsent($this->payseraPaymentSettings->isBuyerConsentEnabled())
            ->setPluginName('WordPress')
            ->setPluginVersion(PAYSERA_PLUGIN_VERSION)
            ->setCmsVersion($wp_version);

        try {
            $paymentRedirectResponse = $checkoutFacade->getPaymentRedirect($redirectRequest);
        } catch (ProviderException $exception) {
            $this->logger->error($exception->getPrevious() ? $exception->getPrevious()->getMessage() : $exception->getMessage());

            return '';
        }

        $this->logger->info(sprintf('Payment request for order id %d: %s', $wcOrder->get_id(), $paymentRedirectResponse->getData()));

        return $paymentRedirectResponse->getRedirectUrl();
    }

    private function limitStringLength(string $value, int $limit = 255): string
    {
        if (strlen($value) > $limit) {
            return substr($value, 0, $limit);
        }

        return $value;
    }

    private function limitIntLength(int $value, int $limit = 255): int
    {
        if (strlen((string) $value) > $limit) {
            return (int) substr((string) $value, 0, $limit);
        }

        return $value;
    }

    private function getLanguage(): string
    {
        $wordpressLanguage = explode('_', get_locale())[0];
        if (PayseraPaymentSettings::ISO_639_2_LANGUAGES[$wordpressLanguage]) {
            return PayseraPaymentSettings::ISO_639_2_LANGUAGES[$wordpressLanguage];
        }

        return PayseraPaymentSettings::DEFAULT_ISO_639_2_LANGUAGE;
    }

    private function getCountryCode(WC_Order $wcOrder): string
    {
        if (
            $wcOrder->get_billing_country() === self::COUNTRY_CODE_SERBIA &&
            in_array($wcOrder->get_billing_state(), self::STATE_CODE_KOSOVO, true)
        ) {
            return self::COUNTRY_CODE_KOSOVO;
        }

        return $this->limitStringLength($wcOrder->get_billing_country(), 2);
    }
}
