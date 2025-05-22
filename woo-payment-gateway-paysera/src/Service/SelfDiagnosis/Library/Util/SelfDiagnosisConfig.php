<?php

declare(strict_types=1);

namespace Paysera\Service\SelfDiagnosis\Library\Util;

class SelfDiagnosisConfig
{
    private array $config;

    public function __construct(SelfDiagnosisConfigLoaderInterface $configLoader)
    {
        $this->config = $configLoader->getConfig();
    }

    public function get(string $key): ?string
    {
        return isset($this->config[$key]) ? (string)$this->config[$key] : null;
    }
}
