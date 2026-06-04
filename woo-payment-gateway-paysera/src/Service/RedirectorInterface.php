<?php

declare(strict_types=1);

namespace Paysera\Service;

defined('ABSPATH') || exit;

interface RedirectorInterface
{
    public function redirect(string $url): void;
}
