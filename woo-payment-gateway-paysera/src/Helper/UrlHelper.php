<?php

declare(strict_types=1);

namespace Paysera\Helper;

defined('ABSPATH') || exit;

class UrlHelper
{
    public static function toAbsolute(string $url): string
    {
        if ($url === '') {
            return $url;
        }

        $parts = wp_parse_url($url);
        if (!empty($parts['scheme'])) {
            return $url;
        }

        if (!empty($parts['host'])) {
            return self::getScheme() . ':' . $url;
        }

        return self::getSiteOrigin() . '/' . ltrim($url, '/');
    }

    public static function buildWcApiUrl(string $endpoint): string
    {
        $home = get_home_url();
        $queryPosition = strpos($home, '?');
        if ($queryPosition !== false) {
            $home = substr($home, 0, $queryPosition);
        }

        return add_query_arg('wc-api', $endpoint, trailingslashit($home));
    }

    private static function getSiteOrigin(): string
    {
        $parts = wp_parse_url(home_url());
        $host = $parts['host'] ?? '';
        $port = isset($parts['port']) ? ':' . $parts['port'] : '';

        return self::getScheme() . '://' . $host . $port;
    }

    private static function getScheme(): string
    {
        $scheme = wp_parse_url(home_url(), PHP_URL_SCHEME);

        return is_string($scheme) && $scheme !== '' ? $scheme : (is_ssl() ? 'https' : 'http');
    }
}
