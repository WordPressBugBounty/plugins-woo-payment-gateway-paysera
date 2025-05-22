<?php

declare(strict_types=1);

namespace Paysera\Service\SelfDiagnosis\Checkers;

use Paysera\Service\SelfDiagnosis\Library\CompatibilityCheckerInterface;
use Paysera\Service\SelfDiagnosis\Library\Result\CheckerResult;

class ActivePluginsChecker extends AbstractChecker implements CompatibilityCheckerInterface
{
    protected const CHECKER_NAME = 'Active Plugins Check';

    private const REQUIRED_FUNCTIONS_NOT_AVAILABLE = 'The required WordPress functions are not available. Ensure this check runs in a WordPress environment.';
    private const NO_ACTIVE_PLUGINS = 'No plugins are currently active.';
    private const PLUGIN_DETAILS_FORMAT = '%s (Version: %s)';
    private const ACTIVE_PLUGINS_TITLE_FORMAT = "The following plugins are active:\n - %s";

    /**
     * Runs the Active Plugins check.
     *
     * @return CheckerResult
     */
    public function check(): CheckerResult
    {
        if (!function_exists('get_plugins') || !function_exists('get_option')) {
            $this->result->isSuccess = false;
            $this->result->details = self::REQUIRED_FUNCTIONS_NOT_AVAILABLE;
            return $this->result;
        }

        $plugins = get_plugins();
        $activePlugins = get_option('active_plugins', []);
        if (empty($activePlugins)) {
            $this->result->isSuccess = true;
            $this->result->details = self::NO_ACTIVE_PLUGINS;
            return $this->result;
        }

        $activePluginDetails = array_map(function ($pluginFile) use ($plugins) {
            if (isset($plugins[$pluginFile])) {
                $pluginData = $plugins[$pluginFile];
                return sprintf(self::PLUGIN_DETAILS_FORMAT, $pluginData['Name'], $pluginData['Version']);
            }
            return $pluginFile;
        }, $activePlugins);

        $this->result->isSuccess = true;

        $this->result->details = sprintf(
            self::ACTIVE_PLUGINS_TITLE_FORMAT,
            implode("\n - ", $activePluginDetails)
        );

        return $this->result;
    }
}
