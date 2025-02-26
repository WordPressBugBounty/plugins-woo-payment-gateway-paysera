<?php

declare(strict_types=1);

defined('ABSPATH') || exit;

if (class_exists('Paysera_Delivery_Gateway') === false) {
    require_once 'abstract-paysera-delivery-gateway.php';
}

class Paysera_Tnt_Terminals_Delivery extends Paysera_Delivery_Gateway
{
    public $deliveryGatewayCode = 'tnt_terminals';
    public $defaultTitle = 'TNT Terminals';
    public $receiverType = 'parcel-machine';
    public $defaultDescription = '%s courier will deliver the parcel to the selected parcel terminal for customer to pickup any time.';
}
