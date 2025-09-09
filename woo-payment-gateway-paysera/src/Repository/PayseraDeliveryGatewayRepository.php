<?php

declare(strict_types=1);

namespace Paysera\Repository;

use Paysera\DeliveryApi\MerchantClient\Entity\Address;
use Paysera\DeliveryApi\MerchantClient\Entity\Order as DeliveryOrder;
use Paysera\Scoped\Paysera\DeliverySdk\Entity\PayseraDeliveryGatewayInterface;
use Paysera\Scoped\Paysera\DeliverySdk\Repository\DeliveryGatewayRepositoryInterface;
use Paysera\Scoped\Paysera\DeliverySdk\Util\DeliveryGatewayUtils;
use Paysera\Helper\PayseraDeliveryHelper;
use WC_Data_Store;
use WC_Shipping_Zone;

class PayseraDeliveryGatewayRepository implements DeliveryGatewayRepositoryInterface
{
    private PayseraDeliveryHelper $deliveryHelper;
    private DeliveryGatewayUtils $deliveryGatewayUtils;

    public function __construct(
        DeliveryGatewayUtils $deliveryGatewayUtils,
        PayseraDeliveryHelper $deliveryHelper
    ) {
        $this->deliveryGatewayUtils = $deliveryGatewayUtils;
        $this->deliveryHelper = $deliveryHelper;
    }

    public function findPayseraGatewayForDeliveryOrder(DeliveryOrder $deliveryOrder): ?PayseraDeliveryGatewayInterface
    {
        $deliveryGatewayCode = $this->deliveryGatewayUtils->getGatewayCodeFromDeliveryOrder($deliveryOrder);
        $data_store = WC_Data_Store::load('shipping-zone');
        $raw_zones = $data_store->get_zones();
        $raw_zones = array_merge($raw_zones, [0]); // add fallback zone

        $shippingAddress = $deliveryOrder->getReceiver()->getContact()->getAddress();

        foreach ($raw_zones as $raw_zone) {
            $zone = new WC_Shipping_Zone($raw_zone);
            $availableShippingMethods = $zone->get_shipping_methods(true, 'admin');

            if (!$this->deliveryHelper->canApplyShippingZone($zone, $shippingAddress)) {
                continue;
            }

            foreach ($availableShippingMethods as $shippingMethod) {
                if (
                    $this->deliveryHelper->isPayseraDeliveryGateway($shippingMethod->id)
                    && $shippingMethod->deliveryGatewayCode === $deliveryGatewayCode
                ) {
                    return $shippingMethod;
                }
            }
        }

        return null;
    }
}
