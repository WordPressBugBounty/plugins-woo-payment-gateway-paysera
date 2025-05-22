<?php

declare(strict_types=1);

namespace Paysera\Service\SelfDiagnosis\Checkers;

use Paysera\Service\SelfDiagnosis\Library\CompatibilityCheckerInterface;
use Paysera\Service\SelfDiagnosis\Library\Result\CheckerResult;

class InstalledPluginsChecker extends AbstractChecker implements CompatibilityCheckerInterface
{
    protected const CHECKER_NAME = 'Installed Plugins Check';

    /**
     * Runs the Installed Plugins check.
     *
     * @return CheckerResult
     */
    public function check(): CheckerResult
    {
        if (!function_exists('get_plugins')) {
            $this->result->isSuccess = false;
            $this->result->details = 'The WordPress function get_plugins() is not available. Ensure this check runs in a WordPress environment.';
            return $this->result;
        }

        $plugins = get_plugins();
        if (empty($plugins)) {
            $this->result->isSuccess = true;
            $this->result->details = 'No plugins are installed.';
            return $this->result;
        }

        $installedPlugins = array_map(function ($pluginData, $pluginFile) {
            return sprintf('%s (Version: %s)', $pluginData['Name'], $pluginData['Version']);
        }, $plugins, array_keys($plugins));

        $this->result->isSuccess = true;
        $this->result->details = sprintf(
            "The following plugins are installed:\n - %s",
            implode("\n - ", $installedPlugins)
        );

        return $this->result;
    }
}
