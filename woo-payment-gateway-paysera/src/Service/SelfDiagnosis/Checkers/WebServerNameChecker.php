<?php

declare(strict_types=1);

namespace Paysera\Service\SelfDiagnosis\Checkers;

use Paysera\Service\SelfDiagnosis\Library\CompatibilityCheckerInterface;
use Paysera\Service\SelfDiagnosis\Library\Result\CheckerResult;

class WebServerNameChecker extends AbstractChecker implements CompatibilityCheckerInterface
{
    protected const CHECKER_NAME = 'Web Server Name Check';
    private const SUCCESS_MESSAGE_FORMAT = 'The web server is detected as \'%s\'.';
    private const FAILURE_MESSAGE = 'The web server name could not be detected. Ensure the $_SERVER["SERVER_SOFTWARE"] variable is available.';

    /**
     * Runs the Web Server Name check.
     *
     * @return CheckerResult
     */
    public function check(): CheckerResult
    {
        $serverSoftware = $_SERVER['SERVER_SOFTWARE'] ?? null;

        if ($serverSoftware !== null) {
            $this->result->isSuccess = true;
            $this->result->details = sprintf(
                self::SUCCESS_MESSAGE_FORMAT,
                $serverSoftware
            );
        } else {
            $this->result->isSuccess = false;
            $this->result->details = self::FAILURE_MESSAGE;
        }

        return $this->result;
    }
}
