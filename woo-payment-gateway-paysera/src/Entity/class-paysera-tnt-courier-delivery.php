<?php

declare(strict_types=1);

defined('ABSPATH') || exit;

if (class_exists('Paysera_Delivery_Gateway') === false) {
    require_once 'abstract-paysera-delivery-gateway.php';
}

class Paysera_Tnt_Courier_Delivery extends Paysera_Delivery_Gateway
{
    public $deliveryGatewayCode = 'tnt_courier';
    public $defaultTitle = 'TNT Courier';
    public $receiverType = 'courier';
    public $defaultDescription = '%s courier will deliver the parcel right to the customer\'s hands.';
}
