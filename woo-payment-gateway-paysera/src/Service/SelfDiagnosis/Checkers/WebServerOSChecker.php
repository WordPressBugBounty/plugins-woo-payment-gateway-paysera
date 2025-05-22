<?php

declare(strict_types=1);

namespace Paysera\Service\SelfDiagnosis\Checkers;

use Paysera\Service\SelfDiagnosis\Library\CompatibilityCheckerInterface;
use Paysera\Service\SelfDiagnosis\Library\Result\CheckerResult;

class WebServerOSChecker extends AbstractChecker implements CompatibilityCheckerInterface
{
    protected const CHECKER_NAME = 'Web Server OS Check';
    private const SUCCESS_MESSAGE_FORMAT = 'The web server is running on \'%s\'. Detailed information: %s';
    private const FAILURE_MESSAGE = 'The web server operating system could not be detected. Ensure PHP_OS_FAMILY and php_uname() are available.';

    /**
     * Runs the Web Server OS check.
     *
     * @return CheckerResult
     */
    public function check(): CheckerResult
    {
        $osFamily = PHP_OS_FAMILY;
        $osDetails = php_uname();

        if (!empty($osFamily) && !empty($osDetails)) {
            $this->result->isSuccess = true;
            $this->result->details = sprintf(
                self::SUCCESS_MESSAGE_FORMAT,
                $osFamily,
                $osDetails
            );
        } else {
            $this->result->isSuccess = false;
            $this->result->details = self::FAILURE_MESSAGE;
        }

        return $this->result;
    }
}
