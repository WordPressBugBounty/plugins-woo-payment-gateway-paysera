<?php

declare(strict_types=1);

namespace Paysera\Service\SelfDiagnosis\Checkers;

use Paysera\Service\SelfDiagnosis\Library\CompatibilityCheckerInterface;
use Paysera\Service\SelfDiagnosis\Library\Result\CheckerResult;
use Paysera\Service\SelfDiagnosis\Library\Util\SelfDiagnosisConfig;

class TLSVersionsChecker extends AbstractChecker implements CompatibilityCheckerInterface
{
    protected const CHECKER_NAME = 'TLS Versions Support Check';
    private const NOT_SUPPORTED_MESSAGE = 'TLS %s: Not Supported (cURL is not compiled with the required TLS version)';
    private const SUPPORTED_MESSAGE = 'TLS %s: Supported';
    private const NOT_SUPPORTED_ERROR_MESSAGE = 'TLS %s: Not Supported (%s)';

    private array $tlsVersions;

    public function __construct(SelfDiagnosisConfig $config, array $tlsVersions)
    {
        parent::__construct($config);

        $this->tlsVersions = $tlsVersions;
    }

    /**
     * Runs the TLS versions support check.
     *
     * @return CheckerResult
     */
    public function check(): CheckerResult
    {
        $url = $this->config->get('public_key_url');
        $results = [];

        foreach ($this->tlsVersions as $versionName => $tlsVersionConstant) {
            if ($tlsVersionConstant === null) {
                $results[] = sprintf(self::NOT_SUPPORTED_MESSAGE, $versionName);
                continue;
            }
            $results[] = $this->testTLSVersion($url, $versionName, $tlsVersionConstant);
        }

        $resultDetails = implode("\n", $results);
        $this->result->isSuccess = true;
        $this->result->details = $resultDetails;

        return $this->result;
    }

    /**
     * Tests if a specific TLS version is supported by the server.
     *
     * @param string $url The URL to test against.
     * @param string $versionName The name of the TLS version (e.g., "TLS 1.2").
     * @param int $tlsVersionConstant The cURL constant for the TLS version.
     * @return string The result of the TLS version test.
     */
    private function testTLSVersion(string $url, string $versionName, int $tlsVersionConstant): string
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSLVERSION, $tlsVersionConstant);

        curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return sprintf(self::NOT_SUPPORTED_ERROR_MESSAGE, $versionName, $error);
        }

        return sprintf(self::SUPPORTED_MESSAGE, $versionName);
    }
}
