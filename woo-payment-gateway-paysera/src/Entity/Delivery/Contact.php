<?php

declare(strict_types=1);

namespace Paysera\Entity\Delivery;

use Paysera\Scoped\Paysera\DeliverySdk\Entity\MerchantOrderContactInterface;
use Paysera\Scoped\Psr\Container\ContainerInterface;
use Paysera\Service\CompatibilityManager;
use WC_Order;

class Contact extends AbstractEntity implements MerchantOrderContactInterface
{
    use WcOrderPartyPropertiesAccess;

    private const FIELD_FIRST_NAME = 'first_name';
    private const FIELD_LAST_NAME  = 'last_name';
    private const FIELD_COMPANY = 'company';
    private const FIELD_PHONE = 'phone';

    private WC_Order $order;
    private string $type;
    private CompatibilityManager $compatibilityManager;

    public function __construct(
        WC_Order $wcOrder,
        string $type,
        CompatibilityManager $compatibilityManager
    ) {
        $this->order = $wcOrder;
        $this->type = $type;
        $this->compatibilityManager = $compatibilityManager;
    }

    public function getFirstName(): string
    {
        return (string)$this->getFromWcOrder(self::FIELD_FIRST_NAME);
    }

    public function getLastName(): string
    {
        return (string)$this->getFromWcOrder(self::FIELD_LAST_NAME);
    }

    public function getCompany(): ?string
    {
        return $this->getFromWcOrder(self::FIELD_COMPANY);
    }

    public function setPhone(?string $phone): MerchantOrderContactInterface
    {
        $this->setToWcOrder(self::FIELD_PHONE, $phone ?? '');

        return $this;
    }

    public function getPhone(): string
    {
        return $this->compatibilityManager
            ->Order($this->order)
            ->getShippingPhone()
        ;
    }

    public function setEmail(?string $email): MerchantOrderContactInterface
    {
        $this->order->set_billing_email($email ?? '');

        return $this;
    }

    public function getEmail(): string
    {
        return $this->order->get_billing_email();
    }
}
