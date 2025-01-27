<?php

declare(strict_types=1);

namespace Paysera\Helper;

defined('ABSPATH') || exit;

use Exception;
use Paysera\Scoped\Paysera\CheckoutSdk\CheckoutFacadeFactory;
use Paysera\Scoped\Paysera\CheckoutSdk\Entity\Collection\PaymentMethodCountryCollection;
use Paysera\Scoped\Paysera\CheckoutSdk\Entity\Request\PaymentMethodsRequest;
use Paysera\Scoped\Psr\Container\ContainerExceptionInterface;
use Paysera\Scoped\Psr\Container\NotFoundExceptionInterface;
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
            $checkoutFacade = (new CheckoutFacadeFactory())->create();
            $request = new PaymentMethodsRequest(
                $projectId,
                (int) number_format($amount, 2, '', ''),
                $currency
            );

            $collection = $checkoutFacade->getPaymentMethods($request);
        } catch (NotFoundExceptionInterface|ContainerExceptionInterface|Exception $exception) {
            $this->logger->error('Error while getting payment method list', $exception);

            return null;
        }

        return $collection;
    }
}
