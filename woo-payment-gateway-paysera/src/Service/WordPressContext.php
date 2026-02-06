<?php

declare(strict_types=1);

namespace Paysera\Service;

class WordPressContext implements WordPressContextInterface
{
    /**
     * Check if the current request is for an administrative interface page
     */
    public function isAdmin(): bool
    {
        return is_admin();
    }

    /**
     * Check if the current request is a REST API request
     */
    public function isRestApi(): bool
    {
        return defined('REST_REQUEST') && REST_REQUEST;
    }

    /**
     * Check if the current request is an AJAX request
     */
    public function isAjax(): bool
    {
        return wp_doing_ajax();
    }
}
