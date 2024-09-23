<?php

declare(strict_types=1);

namespace Paysera\CheckoutSdk\Provider\WebToPay\Adapter;

use Paysera\CheckoutSdk\Entity\Request\PaymentRedirectRequest;
use WebToPay;

class PaymentRedirectRequestNormalizer
{
    public function normalize(PaymentRedirectRequest $request): array
    {
        $paymentData = [
            'orderid' => $request->getOrder()->getOrderId(),
            'amount' => $request->getOrder()->getAmount(),
            'currency' => $request->getOrder()->getCurrency(),
            'accepturl' => $request->getAcceptUrl(),
            'cancelurl' => $request->getCancelUrl(),
            'callbackurl' => $request->getCallbackUrl(),
            'version' => $request->getVersion() ?? WebToPay::VERSION,
            'lang' => $request->getLanguage(),
            'payment' => $request->getPayment(),
            'country' => $request->getCountry(),
            'paytext' => $request->getPaymentText(),
            'p_firstname' => $request->getOrder()->getPayerFirstName(),
            'p_lastname' => $request->getOrder()->getPayerLastName(),
            'p_email' => $request->getOrder()->getPayerEmail(),
            'p_street' => $request->getOrder()->getPayerStreet(),
            'p_city' => $request->getOrder()->getPayerCity(),
            'p_zip' => $request->getOrder()->getPayerZip(),
            'p_state' => $request->getOrder()->getPayerState(),
            'p_countrycode' => $request->getOrder()->getPayerCountryCode(),
            'test' => (int) $request->getTest(),
            'buyer_consent' => (int) $request->getBuyerConsent(),
            'time_limit' => $request->getTimeLimit(),
            'plugin_name' => $request->getPluginName(),
            'plugin_version' => $request->getPluginVersion(),
            'cms_version' => $request->getCmsVersion(),
            'php_version' => phpversion(),
        ];

        return array_filter($paymentData, static fn ($value) => $value !== null);
    }
}
