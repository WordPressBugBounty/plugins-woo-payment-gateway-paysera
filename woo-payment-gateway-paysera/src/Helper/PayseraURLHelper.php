<?php
declare(strict_types=1);

namespace Paysera\Helper;

use WP;
use WP_MatchesMapRegex;
use WP_Query;
use WP_Rewrite;
use WP_Post;

class PayseraURLHelper
{
    private WP $wp;

    private WP_Rewrite $wpRewrite;
    
    private string $homeUrl;
    
    public function __construct()
    {
        global $wp;
        global $wp_rewrite;
        
        $this->wp = $wp;
        $this->wpRewrite = $wp_rewrite;
        $this->homeUrl = home_url();
    }

    /**
     *
     * Simplified variant of url_to_postid() WordPress function to find id of page much faster as original function
     *
     *
     * Examines a URL and try to determine the page ID it represents.
     *
     * Checks are supposedly from the hosted site blog.
     *
     * @param string $url Permalink to check.
     * @return int|null Post ID, or null on failure.
     */
    public function urlToPageId(string $url): ?int
    {
        if ($this->hostBelongsToTheSite($url) === false) {
            return null;
        }

        $pageId = $this->getPageIdFromPlainUrl($url);

        if ($pageId !== null) {
            return $pageId;
        }

        $url = $this->prepareUrl($url);

        return $this->getIdIfPageOnFront($url)
            ?? $this->getIdBasedOnRewriteRules($url);
    }

    private function hostBelongsToTheSite(string $url): bool
    {
        $urlHost = $this->getHost($url);
        $homeUrlHost = $this->getHost($url);

        return $urlHost !== '' && $urlHost === $homeUrlHost;
    }

    private function getHost(string $url): string
    {
        $urlHost = parse_url($url, PHP_URL_HOST);

        return is_string($urlHost) ? str_replace('www.', '', $urlHost) : '';
    }

    private function getPageIdFromPlainUrl(string $url): ?int
    {
        if (preg_match('#[?&](p|page_id)=(\d+)#', $url, $values)) {
            $id = absint($values[2]);

            if ($id) {
                return $id;
            }
        }

        return null;
    }

    private function prepareUrl(string $initialUrl): string
    {
        $url = $this->cleanUrl($initialUrl);
        $scheme = parse_url($this->homeUrl, PHP_URL_SCHEME);
        $url = set_url_scheme($url, $scheme);

        return preg_match('/\:\/\/www\./', $this->homeUrl)
            ? str_replace('://', '://www.', $url)
            : str_replace('://www.', '://', $url);
    }

    private function cleanUrl(string $initialUrl): string
    {
        $parsedUrl = parse_url($initialUrl);

        $url = $parsedUrl['scheme'] . "://" . $parsedUrl['host'];

        if (isset($parsedUrl['port'])) {
            $url .= ":" . $parsedUrl['port'];
        }

        if (isset($parsedUrl['path'])) {
            $url .= $parsedUrl['path'];
        }

        return $url;
    }

    private function getIdIfPageOnFront(string $url): ?int
    {
        if (trim($url, '/') === $this->homeUrl && 'page' === get_option('show_on_front')) {
            $pageOnFront = get_option('page_on_front');

            if ($pageOnFront && get_post($pageOnFront) instanceof WP_Post) {
                return (int)$pageOnFront;
            }
        }

        return null;
    }

    private function getIdBasedOnRewriteRules(string $initialUrl): ?int
    {
        $rewrite = $this->wpRewrite->wp_rewrite_rules();

        if (empty($rewrite)) {
            return null;
        }

        $url = $this->getUrlForMatch($initialUrl);

        $request = $url;

        $requestMatch = $request;

        foreach ((array)$rewrite as $match => $query) {
            $requestMatch = $this->getRequestMatch($url, $request, $match, $requestMatch);

            $page = $this->getMatchedPageId($match, $requestMatch, $query);

            if ($page !== null) {
                return $page;
            }
        }

        return null;
    }

    private function getUrlForMatch(string $initialUrl): ?string
    {
        $url = $initialUrl;
        if (!$this->wpRewrite->using_index_permalinks()) {
            $url = str_replace($this->wpRewrite->index . '/', '', $url);
        }

        if (strpos(trailingslashit($url), home_url('/')) !== false) {
            $url = str_replace($this->homeUrl, '', $url);
        } else {
            $homePath = parse_url(home_url('/'));
            $homePath = $homePath['path'] ?? '';
            $url = preg_replace(sprintf('#^%s#', preg_quote($homePath)), '', trailingslashit($url));
        }

        return trim($url, '/');
    }

    private function getPostIdWithWpQuery(string $query, array $matches): ?int
    {
        $query = preg_replace('!^.+\?!', '', $query);

        $query = addslashes(WP_MatchesMapRegex::apply($query, $matches));

        parse_str($query, $query_vars);

        $query = [
            'post_type' => 'page'
        ];

        foreach ((array)$query_vars as $key => $value) {
            if (in_array((string)$key, $this->wp->public_query_vars, true)) {
                $query[$key] = $value;
            }
        }

        $query = wp_resolve_numeric_slug_conflicts($query);

        $query = new WP_Query($query);
        if (!empty($query->posts) && $query->is_singular) {
            return $query->post->ID;
        }

        return null;
    }

    private function getRequestMatch(?string $url, ?string $request, string $match, string $requestMatch): string
    {
        if (!empty($url) && ($url !== $request) && str_starts_with($match, $url)) {
            return $url . '/' . $request;
        }

        return $requestMatch;
    }

    private function getMatchedPageId(string $match, string $requestMatch, string $query): ?int
    {
        if (preg_match("#^$match#", $requestMatch, $matches)) {
            if ($this->wpRewrite->use_verbose_page_rules && preg_match('/pagename=\$matches\[([0-9]+)]/', $query, $varmatch)) {
                $page = get_page_by_path($matches[$varmatch[1]]);

                if ($page === null) {
                    return null;
                }

                $postStatusObj = get_post_status_object($page->post_status);
                if (
                    $postStatusObj->public
                    || $postStatusObj->protected
                    || $postStatusObj->private
                    || !$postStatusObj->exclude_from_search
                ) {
                    return $page->ID;
                }
            }

            if (
                isset($page) === false
                || isset($varmatch)
                && is_numeric($matches[$varmatch[1]])
            ) {
                return $this->getPostIdWithWpQuery($query, $matches);
            }
        }

        return null;
    }
}
