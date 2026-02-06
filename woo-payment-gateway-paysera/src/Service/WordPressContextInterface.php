<?php

declare(strict_types=1);

namespace Paysera\Service;

interface WordPressContextInterface
{
    /**
     * Check if the current request is for an administrative interface page
     */
    public function isAdmin(): bool;

    /**
     * Check if the current request is a REST API request
     */
    public function isRestApi(): bool;

    /**
     * Check if the current request is an AJAX request
     */
    public function isAjax(): bool;
}
