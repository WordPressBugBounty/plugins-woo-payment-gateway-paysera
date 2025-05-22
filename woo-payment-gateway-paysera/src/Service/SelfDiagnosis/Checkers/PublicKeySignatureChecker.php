<?php

declare(strict_types=1);

namespace Paysera\Service\SelfDiagnosis\Checkers;

use Paysera\Service\SelfDiagnosis\Library\CompatibilityCheckerInterface;
use Paysera\Service\SelfDiagnosis\Library\Result\CheckerResult;
use Paysera\Service\SelfDiagnosis\Library\Util\SelfDiagnosisConfig;

class PublicKeySignatureChecker extends AbstractChecker implements CompatibilityCheckerInterface
{
    protected const CHECKER_NAME = 'SS2 Signature Verification Check';

    private const PUBLIC_KEY_URL_KEY = 'public_key_url';

    private const OPENSSL_ERROR_MESSAGE = 'The OpenSSL extension is not loaded. Ensure the extension is enabled.';
    private const FAILED_TO_FETCH_PUBLIC_KEY_MESSAGE = 'Failed to fetch the public key from the specified URL.';
    private const FAILED_TO_EXTRACT_PUBLIC_KEY_MESSAGE = 'Failed to extract the public key from the certificate.';
    private const FAILED_TO_DECODE_SIGNATURE_MESSAGE = 'Failed to decode the signature.';
    private const PK_CHECK_PASSED_MESSAGE = 'Public key verification check passed!';

    private string $publicKeyUrl;

    public function __construct(SelfDiagnosisConfig $config)
    {
        parent::__construct($config);

        $this->publicKeyUrl = $config->get(self::PUBLIC_KEY_URL_KEY);
    }

    /**
     * Runs the SSL signature verification check.
     *
     * @return CheckerResult
     */
    public function check(): CheckerResult
    {
        if (extension_loaded('openssl') === false) {
            $this->result->isSuccess = false;
            $this->result->details = self::OPENSSL_ERROR_MESSAGE;
            return $this->result;
        }

        try {
            $certificate = file_get_contents($this->publicKeyUrl);
        } catch (\Exception $e) {
            $certificate = false;
        }

        if ($certificate === false) {
            $this->result->isSuccess = false;
            $this->result->details = self::FAILED_TO_FETCH_PUBLIC_KEY_MESSAGE;
            return $this->result;
        }

        $publicKeyResource = openssl_pkey_get_public($certificate);
        if (!$publicKeyResource) {
            $this->result->isSuccess = false;
            $this->result->details = self::FAILED_TO_EXTRACT_PUBLIC_KEY_MESSAGE;
            return $this->result;
        }

        $certDetails = openssl_x509_parse($certificate);
        $keyDetails = openssl_pkey_get_details($publicKeyResource);
        if (!$certDetails || !$keyDetails) {
            $this->result->isSuccess = false;
            $this->result->details = self::FAILED_TO_DECODE_SIGNATURE_MESSAGE;

            return $this->result;
        }

        $this->result->isSuccess = true;
        $this->result->details = self::PK_CHECK_PASSED_MESSAGE;

        return $this->result;
    }
}
