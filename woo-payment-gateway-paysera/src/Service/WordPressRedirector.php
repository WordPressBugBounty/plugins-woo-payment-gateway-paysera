<?php

declare(strict_types=1);

namespace Paysera\Service;

defined('ABSPATH') || exit;

class WordPressRedirector implements RedirectorInterface
{
    public function redirect(string $url): void
    {
        wp_safe_redirect($url);
        $this->halt();
    }

    protected function halt(): void
    {
        exit;
    }
}
