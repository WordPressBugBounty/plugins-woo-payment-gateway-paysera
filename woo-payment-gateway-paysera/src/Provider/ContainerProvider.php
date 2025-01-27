<?php

declare(strict_types=1);

namespace Paysera\Provider;

use Paysera\Scoped\Psr\Container\ContainerInterface;
use Paysera\Scoped\Symfony\Component\Config\FileLocator;
use Paysera\Scoped\Symfony\Component\DependencyInjection\ContainerBuilder;
use Paysera\Scoped\Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class ContainerProvider
{
    private const DEPENDENCY_CONTAINER_KEY = 'paysera_container';
    private const CONFIGS_DIR_PATH = __DIR__ . '/../Resources/config';
    private const PARAMETERS_PATH = self::CONFIGS_DIR_PATH . '/parameters.php';

    public function getContainer(): ContainerInterface
    {
        $dependencyContainer = apply_filters(self::DEPENDENCY_CONTAINER_KEY, null);

        if ($dependencyContainer === null) {
            $dependencyContainer = $this->buildContainer();

            add_filter(
                self::DEPENDENCY_CONTAINER_KEY,
                fn () => $dependencyContainer,
                PHP_INT_MAX
            );
        }

        return $dependencyContainer;
    }

    public function buildContainer(): ContainerInterface
    {
        $containerBuilder = new ContainerBuilder();
        $loader = new YamlFileLoader($containerBuilder, new FileLocator(self::CONFIGS_DIR_PATH));

        $loader->load('services.yaml');
        $this->initParameters($containerBuilder);

        $containerBuilder->compile();

        return $containerBuilder;
    }

    private function initParameters(ContainerBuilder $containerBuilder): void
    {
        $parameters = include self::PARAMETERS_PATH;

        foreach ($parameters as $key => $value) {
            $containerBuilder->setParameter($key, $value);
        }
    }
}
