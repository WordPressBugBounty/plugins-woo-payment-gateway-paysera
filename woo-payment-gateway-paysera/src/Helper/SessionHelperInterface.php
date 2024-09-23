<?php

declare(strict_types=1);

namespace Paysera\Helper;

interface SessionHelperInterface
{
    public function setData($key, $value);

    public function getData($key, $default = '');

    public function isSessionAvailable(): bool;
}
