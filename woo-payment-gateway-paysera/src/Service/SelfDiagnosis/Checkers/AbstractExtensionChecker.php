<?php

declare(strict_types=1);

namespace Paysera\Service\SelfDiagnosis\Checkers;

use Paysera\Service\SelfDiagnosis\Library\Result\CheckerResult;

abstract class AbstractExtensionChecker extends AbstractChecker
{
    private const CHECKER_NAME_FORMAT = 'Check %s extension enabled';
    private const SUCCESS_MESSAGE_FORMAT = '%s extension is enabled (version %s)';
    private const FAILURE_MESSAGE_FORMAT = '%s extension is disabled. Please enable %s';


    protected function checkExtension(string $extensionName): CheckerResult
    {
        $isLoaded = extension_loaded($extensionName);

        $this->result->checkName = sprintf(self::CHECKER_NAME_FORMAT, $extensionName);
        $this->result->isSuccess = $isLoaded;
        $this->result->details = $this->getMessage($isLoaded, $extensionName);

        return $this->result;
    }

    private function getMessage(bool $isLoaded, string $extensionName): string
    {
        if ($isLoaded) {
            $version = (string) phpversion($extensionName);
            $message = sprintf(self::SUCCESS_MESSAGE_FORMAT, $extensionName, $version);
        } else {
            $message = sprintf(self::FAILURE_MESSAGE_FORMAT, $extensionName, $extensionName);
        }

        return $message;
    }
}
