<?php

declare(strict_types=1);

namespace Paysera\Service\SelfDiagnosis;

use Paysera\Service\SelfDiagnosis\Checkers\ActivePluginsChecker;
use Paysera\Service\SelfDiagnosis\Checkers\ActiveThemeChecker;
use Paysera\Service\SelfDiagnosis\Checkers\InstalledPluginsChecker;
use Paysera\Service\SelfDiagnosis\Checkers\WooCommerceVersionChecker;
use Paysera\Service\SelfDiagnosis\Checkers\WordPressVersionChecker;
use Paysera\Service\SelfDiagnosis\Library\CompatibilityCheckerRegistry;

class WoocommerceCompatibilityCheckerRegistry extends CompatibilityCheckerRegistry
{
    public function __construct()
    {
        parent::__construct();

        $this->registerChecker(self::PLATFORM_CATEGORY, WordPressVersionChecker::class);
        $this->registerChecker(self::PLATFORM_CATEGORY, WooCommerceVersionChecker::class);
        $this->registerChecker(self::PLATFORM_CATEGORY, ActiveThemeChecker::class);
        $this->registerChecker(self::PLATFORM_CATEGORY, InstalledPluginsChecker::class);
        $this->registerChecker(self::PLATFORM_CATEGORY, ActivePluginsChecker::class);
    }

}
