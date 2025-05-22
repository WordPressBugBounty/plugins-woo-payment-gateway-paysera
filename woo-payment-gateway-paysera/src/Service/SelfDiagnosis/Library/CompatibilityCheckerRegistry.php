<?php

declare(strict_types=1);

namespace Paysera\Service\SelfDiagnosis\Library;

use Paysera\Service\SelfDiagnosis\Checkers\AllowUrlFopenPHPIniVarChecker;
use Paysera\Service\SelfDiagnosis\Checkers\BCMathExtensionChecker;
use Paysera\Service\SelfDiagnosis\Checkers\DefaultCharsetPHPIniVarChecker;
use Paysera\Service\SelfDiagnosis\Checkers\GetRequestLengthSupportChecker;
use Paysera\Service\SelfDiagnosis\Checkers\MaxInputVarsPHPIniVarChecker;
use Paysera\Service\SelfDiagnosis\Checkers\MemoryLimitPHPIniVarChecker;
use Paysera\Service\SelfDiagnosis\Checkers\OpenSSLExtensionChecker;
use Paysera\Service\SelfDiagnosis\Checkers\PHPVersionChecker;
use Paysera\Service\SelfDiagnosis\Checkers\PostMaxSizePHPIniVarChecker;
use Paysera\Service\SelfDiagnosis\Checkers\PublicKeySignatureChecker;
use Paysera\Service\SelfDiagnosis\Checkers\SSLVersionChecker;
use Paysera\Service\SelfDiagnosis\Checkers\TLSVersionsChecker;
use Paysera\Service\SelfDiagnosis\Checkers\WebServerNameChecker;
use Paysera\Service\SelfDiagnosis\Checkers\WebServerOSChecker;

class CompatibilityCheckerRegistry implements CompatibilityCheckerRegistryInterface
{
    public const PLATFORM_CATEGORY = 'platform';
    public const PHP_CATEGORY = 'PHP';
    public const SECURITY_CATEGORY = 'security';

    /**
     * @var string[][]
     */
    private array $checkers = [
        self::PHP_CATEGORY => [],
        self::SECURITY_CATEGORY => [],
        self::PLATFORM_CATEGORY => [],
    ];

    public function __construct()
    {
        $this->registerCommonCheckers();
    }

    /**
     * Registers a checker.
     *
     * @param string $category
     * @param string $checkerClass
     */
    public function registerChecker(string $category, string $checkerClass)
    {
        $this->checkers[$category][] = $checkerClass;
    }

    /**
     * Retrieves all registered checkers.
     *
     * @return string[][]
     */
    public function getCheckers(): array
    {
        return $this->checkers;
    }

    private function registerCommonCheckers(): void
    {
        $this->registerChecker(self::PHP_CATEGORY, PHPVersionChecker::class);
        $this->registerChecker(self::PHP_CATEGORY, GetRequestLengthSupportChecker::class);
        $this->registerChecker(self::PHP_CATEGORY, BCMathExtensionChecker::class);
        $this->registerChecker(self::PHP_CATEGORY, OpenSSLExtensionChecker::class);
        $this->registerChecker(self::PHP_CATEGORY, MemoryLimitPHPIniVarChecker::class);
        $this->registerChecker(self::PHP_CATEGORY, MaxInputVarsPHPIniVarChecker::class);
        $this->registerChecker(self::PHP_CATEGORY, PostMaxSizePHPIniVarChecker::class);
        $this->registerChecker(self::PHP_CATEGORY, DefaultCharsetPHPIniVarChecker::class);
        $this->registerChecker(self::PHP_CATEGORY, AllowUrlFopenPHPIniVarChecker::class);

        $this->registerChecker(self::SECURITY_CATEGORY, PublicKeySignatureChecker::class);
        $this->registerChecker(self::SECURITY_CATEGORY, SSLVersionChecker::class);
        $this->registerChecker(self::SECURITY_CATEGORY, TLSVersionsChecker::class);

        $this->registerChecker(self::PLATFORM_CATEGORY, WebServerOSChecker::class);
        $this->registerChecker(self::PLATFORM_CATEGORY, WebServerNameChecker::class);
    }
}
