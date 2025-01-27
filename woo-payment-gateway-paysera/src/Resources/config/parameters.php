<?php

declare(strict_types=1);

use Paysera\Entity\PayseraDeliverySettings;
use Paysera\Helper\LogHelper;

global $wpdb;

return [
    'wpdb_prefix' => $wpdb->prefix,
    'event.delivery_order.updated' => PayseraDeliverySettings::DELIVERY_ORDER_EVENT_UPDATED,
    'event.wc_order.created' => PayseraDeliverySettings::WC_ORDER_EVENT_CREATED,
    'event.wc_order.paid' => PayseraDeliverySettings::WC_ORDER_EVENT_PAYMENT_COMPLETED,
];
