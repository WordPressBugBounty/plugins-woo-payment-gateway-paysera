<?php

declare(strict_types=1);

namespace Paysera\Helper;

defined('ABSPATH') || exit;

use Paysera\Scoped\Paysera\CheckoutSdk\CheckoutFacade;
use Paysera\Scoped\Paysera\CheckoutSdk\CheckoutFacadeFactory;
use Paysera\Scoped\Paysera\CheckoutSdk\Entity\Collection\PaymentMethodCountryCollection;
use Paysera\Scoped\Paysera\CheckoutSdk\Entity\Request\PaymentMethodsRequest;
use Paysera\Service\PaymentLoggerInterface;

class PayseraPaymentLibraryHelper
{
    private PaymentLoggerInterface $logger;

    public function __construct(PaymentLoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function getPaymentMethodList(int $projectId, float $amount, string $currency): ?PaymentMethodCountryCollection
    {
        try {
            $checkoutFacade = $this->createCheckoutFacade();
            $request = new PaymentMethodsRequest(
                $projectId,
                (int) number_format($amount, 2, '', ''),
                $currency
            );

            $collection = $checkoutFacade->getPaymentMethods($request);
        } catch (\Throwable $exception) {
            $this->logger->error('Error while getting payment method list', $exception);

            return null;
        }

        return $collection;
    }

    protected function createCheckoutFacade(): CheckoutFacade
    {
        return (new CheckoutFacadeFactory())->create();
    }
}
