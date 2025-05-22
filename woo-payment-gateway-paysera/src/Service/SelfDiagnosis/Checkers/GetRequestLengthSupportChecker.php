<?php

declare(strict_types=1);

namespace Paysera\Service\SelfDiagnosis\Checkers;

use Paysera\Service\SelfDiagnosis\Library\CompatibilityCheckerInterface;
use Paysera\Service\SelfDiagnosis\Library\Result\CheckerResult;

class GetRequestLengthSupportChecker extends AbstractChecker implements CompatibilityCheckerInterface
{
    protected const CHECKER_NAME = 'GET Request Length Support Check';
    private const SUCCESS_DETAILS_MESSAGE = 'The server supports GET request URLs with query strings of %d characters.';
    private const FAILURE_DETAILS_414_MESSAGE = 'The server does not support GET request URLs with query strings of %d characters. A \'414 URI Too Long\' error was returned.';
    private const FAILURE_DETAILS_UNEXPECTED_MESSAGE = 'The server returned an unexpected response code (%d) when testing a GET request with a query string of %d characters.';
    private const QUERY_STRING_PARAMETER_NAME = 'get_length_test';

    /**
     * Runs the GET request length support check.
     *
     * @return CheckerResult
     */
    public function check(): CheckerResult
    {
        if (isset($_GET[self::QUERY_STRING_PARAMETER_NAME])) {
            $this->result->isSuccess = true;
            $this->result->details = self::SUCCESS_DETAILS_MESSAGE;

            return $this->result;
        }

        $requiredLength = (int)$this->config->get('get_request_required_length');
        $url = $this->getBaseUrl();
        $queryString = str_repeat('a', $requiredLength);
        $testUrl = sprintf(
            '%s?%s=%s',
            $url,
            self::QUERY_STRING_PARAMETER_NAME,
            $queryString
        );

        $responseCode = $this->getResponseCode($testUrl);

        switch ($responseCode) {
            case 200:
                $this->result->isSuccess = true;
                $this->result->details = sprintf(
                    self::SUCCESS_DETAILS_MESSAGE,
                    $requiredLength
                );
                break;
            case 414:
                $this->result->isSuccess = false;
                $this->result->details = sprintf(
                    self::FAILURE_DETAILS_414_MESSAGE,
                    $requiredLength
                );
                break;
            default:
                $this->result->isSuccess = false;
                $this->result->details = sprintf(
                    self::FAILURE_DETAILS_UNEXPECTED_MESSAGE,
                    $responseCode,
                    $requiredLength
                );
                break;
        }

        return $this->result;
    }

    private function getResponseCode(string $url): int
    {
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_NOBODY, true);
        curl_exec($curl);
        $responseCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        return $responseCode;
    }

    /**
     * Gets the base URL for the current server.
     *
     * @return string The base URL.
     */
    private function getBaseUrl(): string
    {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];

        return $protocol . '://' . $host;
    }
}
