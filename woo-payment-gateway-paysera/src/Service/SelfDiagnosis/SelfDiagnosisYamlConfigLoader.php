<?php

declare(strict_types=1);

namespace Paysera\Service\SelfDiagnosis;

use Paysera\Scoped\Symfony\Component\Yaml\Yaml;

class SelfDiagnosisYamlConfigLoader implements Library\Util\SelfDiagnosisConfigLoaderInterface
{
    private string $configPath;

    public function __construct(string $configPath)
    {
        $this->configPath = $configPath;
    }

    public function getConfig(): array
    {
        $data = $this->parseYaml();
        return  $data['settings'] ?? [];
    }

    private function parseYaml(): array
    {
        try {
            $yaml = file_get_contents(sprintf('%s../../%s' , plugin_dir_path(__FILE__), $this->configPath));
        } catch (\Exception $e) {
            throw new \RuntimeException(
                sprintf(
                    'Could not load config file %s',
                    $this->configPath
                )
            );
        }

        return Yaml::parse($yaml);
    }
}
