<?php

declare(strict_types=1);

namespace Paysera\Service\SelfDiagnosis\Library\Util;

class ResponseHeadersUtil
{
    public function addTextFileDownloadHeaders(string $fileName): void
    {
        header('Content-Type: text/plain');
        header(sprintf('Content-Disposition: attachment; filename="%s"', $fileName));
    }

    public function terminateRequest(): void
    {
        exit;
    }

    public function wpSafeRedirect(string $location, int $status = 302): void
    {
        wp_safe_redirect($location, $status);
    }
}
