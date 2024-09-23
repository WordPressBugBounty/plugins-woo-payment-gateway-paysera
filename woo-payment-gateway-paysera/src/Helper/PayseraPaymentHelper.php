<?php

declare(strict_types=1);

namespace Paysera\Helper;

defined('ABSPATH') || exit;

use WC_Countries;

class PayseraPaymentHelper
{
    public function settingsUrl(array $query = []): string
    {
        return esc_url(admin_url('admin.php?page=paysera-payments') . '&' . http_build_query($query));
    }

    public function getWooCommerceCountries(): array
    {
        return (new WC_Countries())->get_countries();
    }

    public function getWooCommerceOrderStatuses(): array
    {
        $statuses = [];

        foreach (array_keys(wc_get_order_statuses()) as $status) {
            $statuses[$status] = wc_get_order_status_name($status);
        }

        return $statuses;
    }
}
