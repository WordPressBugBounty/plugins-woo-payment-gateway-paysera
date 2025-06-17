<?php

declare(strict_types=1);

namespace Paysera\Entity;

defined('ABSPATH') || exit;

class PayseraPaymentSettings
{
    public const MAIN_SETTINGS_NAME = 'paysera_payment_main_settings';
    public const EXTRA_SETTINGS_NAME = 'paysera_payment_extra_settings';
    public const STATUS_SETTINGS_NAME = 'paysera_payment_status_settings';
    public const PROJECT_ADDITIONS_SETTINGS_NAME = 'paysera_payment_project_additions_settings';

    public const ENABLED = 'enabled';
    public const PROJECT_ID = 'project_id';
    public const PROJECT_PASSWORD = 'project_password';
    public const TEST_MODE = 'test_mode';
    public const TITLE = 'title';
    public const DESCRIPTION = 'description';
    public const LIST_OF_PAYMENTS = 'list_of_payments';
    public const SPECIFIC_COUNTRIES = 'specific_countries';
    public const GRID_VIEW = 'grid_view';
    public const BUYER_CONSENT = 'buyer_consent';
    public const PAID_ORDER_STATUS = 'paid_order_status';
    // Old pending checkout status
    public const PENDING_PAYMENT_STATUS = 'pending_checkout_status';
    public const OWNERSHIP_CODE_ENABLED = 'ownership_code_enabled';
    public const OWNERSHIP_CODE = 'ownership_code';
    public const QUALITY_SIGN_ENABLED = 'quality_sign_enabled';
    public const LOG_LEVEL = 'log_level';

    public const DEFAULT_TITLE = 'All popular payment methods';
    public const DEFAULT_DESCRIPTION = 'Choose a payment method on the Paysera page';
    public const DEFAULT_ISO_639_1_LANGUAGE = 'en';
    public const DEFAULT_ISO_639_2_LANGUAGE = 'ENG';

    public const ISO_639_1_LANGUAGES = [
        'lt',
        'lv',
        'et',
        'ru',
        'bg',
        'pl',
        'en',
    ];

    public const ISO_639_2_LANGUAGES = [
        'lt' => 'LIT',
        'lv' => 'LAV',
        'et' => 'EST',
        'ru' => 'RUS',
        'de' => 'GER',
        'pl' => 'POL',
        'en' => 'ENG',
    ];

    private bool $enabled = false;
    private ?int $projectId;
    private ?string $projectPassword;
    private ?bool $testModeEnabled;
    private ?string $title;
    private ?string $description;
    private ?bool $listOfPaymentsEnabled;
    private array $specificCountries;
    private ?bool $gridViewEnabled;
    private ?bool $buyerConsentEnabled;
    private ?string $paidOrderStatus;
    private ?string $pendingPaymentStatus;
    private ?bool $ownershipCodeEnabled;
    private ?string $ownershipCode;
    private ?bool $qualitySignEnabled;
    private ?string $logLevel;

    public function __construct()
    {
        $this->projectId = null;
        $this->projectPassword = null;
        $this->testModeEnabled = null;
        $this->title = null;
        $this->description = null;
        $this->listOfPaymentsEnabled = null;
        $this->specificCountries = [];
        $this->gridViewEnabled = null;
        $this->buyerConsentEnabled = null;
        $this->paidOrderStatus = null;
        $this->pendingPaymentStatus = null;
        $this->ownershipCodeEnabled = null;
        $this->ownershipCode = null;
        $this->qualitySignEnabled = null;
        $this->logLevel = null;
    }

    public function setEnabled(bool $enabled): self
    {
        $this->enabled = $enabled;

        return $this;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function getProjectId(): ?int
    {
        return $this->projectId;
    }

    public function setProjectId(int $projectId): self
    {
        $this->projectId = $projectId;

        return $this;
    }

    public function getProjectPassword(): ?string
    {
        return $this->projectPassword;
    }

    public function setProjectPassword(string $projectPassword): self
    {
        $this->projectPassword = $projectPassword;

        return $this;
    }

    public function isTestModeEnabled(): ?bool
    {
        return $this->testModeEnabled;
    }

    public function setTestModeEnabled(bool $testModeEnabled): self
    {
        $this->testModeEnabled = $testModeEnabled;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title ?: __(self::DEFAULT_TITLE, 'paysera');
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description ?: __(self::DEFAULT_DESCRIPTION, 'paysera');
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function isListOfPaymentsEnabled(): ?bool
    {
        return $this->listOfPaymentsEnabled;
    }

    public function setListOfPaymentsEnabled(bool $listOfPaymentsEnabled): self
    {
        $this->listOfPaymentsEnabled = $listOfPaymentsEnabled;

        return $this;
    }

    public function getSpecificCountries(): array
    {
        return $this->specificCountries;
    }

    public function setSpecificCountries(array $specificCountries): self
    {
        $this->specificCountries = $specificCountries;

        return $this;
    }

    public function isGridViewEnabled(): ?bool
    {
        return $this->gridViewEnabled;
    }

    public function setGridViewEnabled(bool $gridViewEnabled): self
    {
        $this->gridViewEnabled = $gridViewEnabled;

        return $this;
    }

    public function isBuyerConsentEnabled(): ?bool
    {
        return $this->buyerConsentEnabled;
    }

    public function setBuyerConsentEnabled(bool $buyerConsentEnabled): self
    {
        $this->buyerConsentEnabled = $buyerConsentEnabled;

        return $this;
    }

    public function getPaidOrderStatus(): ?string
    {
        return $this->paidOrderStatus;
    }

    public function setPaidOrderStatus(string $paidOrderStatus): self
    {
        $this->paidOrderStatus = $paidOrderStatus;

        return $this;
    }

    public function getPendingPaymentStatus(): ?string
    {
        return $this->pendingPaymentStatus;
    }

    public function setPendingPaymentStatus(string $pendingPaymentStatus): self
    {
        $this->pendingPaymentStatus = $pendingPaymentStatus;

        return $this;
    }

    public function isOwnershipCodeEnabled(): ?bool
    {
        return $this->ownershipCodeEnabled;
    }

    public function setOwnershipCodeEnabled(bool $ownershipCodeEnabled): self
    {
        $this->ownershipCodeEnabled = $ownershipCodeEnabled;

        return $this;
    }

    public function getOwnershipCode(): ?string
    {
        return $this->ownershipCode;
    }

    public function setOwnershipCode(string $ownershipCode): self
    {
        $this->ownershipCode = $ownershipCode;

        return $this;
    }

    public function isQualitySignEnabled(): ?bool
    {
        return $this->qualitySignEnabled;
    }

    public function setQualitySignEnabled(bool $qualitySignEnabled): self
    {
        $this->qualitySignEnabled = $qualitySignEnabled;

        return $this;
    }

    public function getLogLevel(): ?string
    {
        return $this->logLevel;
    }

    public function setLogLevel(string $logLevel): self
    {
        $this->logLevel = $logLevel;

        return $this;
    }

    public function getLanguage(): string
    {
        $localLanguage = explode('_', get_locale())[0];

        return in_array($localLanguage, self::ISO_639_1_LANGUAGES, true) ? $localLanguage : self::DEFAULT_ISO_639_1_LANGUAGE;
    }
}
