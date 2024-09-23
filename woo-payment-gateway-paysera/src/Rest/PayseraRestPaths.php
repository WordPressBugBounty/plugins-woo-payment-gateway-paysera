<?php

declare(strict_types=1);

namespace Paysera\Rest;

class PayseraRestPaths
{
    public const CONTROLLER_BASE = 'delivery';
    public const DELIVERY_VALIDATION = '/'.self::CONTROLLER_BASE.'/validation';
    public const CHECK_ORDER_UPDATES = '/'.self::CONTROLLER_BASE.'/check-order-updates/(?P<id>\d+)';
    public const SET_HOUSE_NO = '/'.self::CONTROLLER_BASE.'/set-house-no';
    public const GET_HOUSE_NO = '/'.self::CONTROLLER_BASE.'/get-house-no';
    public const SET_TERMINAL_LOCATION = '/'.self::CONTROLLER_BASE.'/set-terminal-location';
    public const GET_TERMINAL_LOCATION = '/'.self::CONTROLLER_BASE.'/terminal-locations';
    public const GET_TERMINAL_CITIES = '/'.self::CONTROLLER_BASE.'/terminal-cities';
    public const GET_TERMINAL_COUNTRIES = '/'.self::CONTROLLER_BASE.'/terminal-countries';
}