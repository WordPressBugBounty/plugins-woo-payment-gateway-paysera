<?php

declare(strict_types=1);

namespace Paysera\Helper;

use Paysera\Entity\PayseraPaths;

defined('ABSPATH') || exit;

class SecurityHelper
{
    public function validateAdminRequest(string $nonceAction): void
    {
        $this->validateNonce($nonceAction);
        $this->validatePermissions();
    }

    private function validateNonce(string $nonceAction): void
    {
        if (!isset($_REQUEST['_wpnonce']) || !wp_verify_nonce($_REQUEST['_wpnonce'], $nonceAction)) {
            wp_die(
                __('Security check failed. Please try again.', PayseraPaths::PAYSERA_TRANSLATIONS),
                __('Security Error', PayseraPaths::PAYSERA_TRANSLATIONS),
                ['response' => 403]
            );
        }
    }

    private function validatePermissions(string $capability = 'manage_options'): void
    {
        if (!current_user_can($capability)) {
            wp_die(
                __('You do not have sufficient permissions to access this page.', PayseraPaths::PAYSERA_TRANSLATIONS),
                __('Permission Denied', PayseraPaths::PAYSERA_TRANSLATIONS),
                ['response' => 403]
            );
        }
    }

    public function getValidatedActionParameter(string $paramName): string
    {
        if (!isset($_GET[$paramName])) {
            wp_die(
                sprintf(
                    __('Missing required parameter: %s', PayseraPaths::PAYSERA_TRANSLATIONS),
                    $paramName
                ),
                __('Invalid Request', PayseraPaths::PAYSERA_TRANSLATIONS),
                ['response' => 400]
            );
        }

        $action = sanitize_text_field(wp_unslash($_GET[$paramName]));

        if (!in_array($action, ['enable', 'disable'], true)) {
            wp_die(
                sprintf(
                    __('Invalid action: %s. Expected "enable" or "disable"', PayseraPaths::PAYSERA_TRANSLATIONS),
                    esc_html($action)
                ),
                __('Invalid Request', PayseraPaths::PAYSERA_TRANSLATIONS),
                ['response' => 400]
            );
        }

        return $action;
    }
}
