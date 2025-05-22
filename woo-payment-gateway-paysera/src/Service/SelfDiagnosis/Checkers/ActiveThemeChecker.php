<?php

declare(strict_types=1);

namespace Paysera\Service\SelfDiagnosis\Checkers;

use Paysera\Service\SelfDiagnosis\Library\CompatibilityCheckerInterface;
use Paysera\Service\SelfDiagnosis\Library\Result\CheckerResult;

class ActiveThemeChecker extends AbstractChecker implements CompatibilityCheckerInterface
{
    protected const CHECKER_NAME = 'Active Theme Checker';

    private const REQUIRED_FUNCTION_NOT_AVAILABLE_MESSAGE = 'The required WordPress function wp_get_theme() is not available. Ensure this check runs in a WordPress environment.';
    private const ACTIVE_THEME_TITLE_FORMAT = 'The active theme is \'%s\' (Version: %s).';
    private const PARENT_THEME_TITLE_FORMAT = ' The parent theme is \'%s\' (Version: %s).';
    private const NO_THEME_DETECTED_MESSAGE = 'No active theme could be detected. Ensure the WordPress environment is configured correctly.';

    /**
     * Runs the Active Theme check.
     *
     * @return CheckerResult
     */
    public function check(): CheckerResult
    {
        if (!function_exists('wp_get_theme')) {
            $this->result->isSuccess = false;
            $this->result->details = self::REQUIRED_FUNCTION_NOT_AVAILABLE_MESSAGE;
            return $this->result;
        }

        $theme = wp_get_theme();
        if ($theme->exists()) {
            $this->result->isSuccess = true;
            $this->result->details = sprintf(
                self::ACTIVE_THEME_TITLE_FORMAT,
                $theme->get('Name'),
                $theme->get('Version')
            );

            if ($theme->parent()) {
                $this->result->details .= sprintf(
                    self::PARENT_THEME_TITLE_FORMAT,
                    $theme->parent()->get('Name'),
                    $theme->parent()->get('Version')
                );
            }
        } else {
            $this->result->isSuccess = false;
            $this->result->details = self::NO_THEME_DETECTED_MESSAGE;
        }

        return $this->result;
    }
}
