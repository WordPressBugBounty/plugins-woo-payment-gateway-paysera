<?php

declare(strict_types=1);

namespace Paysera\Dto;

use Paysera\Scoped\Paysera\DeliverySdk\Entity\PayseraDeliverySettingsInterface;

class PayseraSettingsValidationDto implements PayseraDeliverySettingsInterface
{
    private ?int $projectId;
    private ?string $projectPassword;
    private ?string $resolvedProjectId;
    private ?bool $isTestModeEnabled;
    private bool $isSinglePerOrderShipmentEnabled;
    private ?bool $enableNumberField;
    private ?string $userAgent;

    public function __construct(
        ?string $projectId = null,
        ?string $projectPassword = null,
        string $userAgent = '',
        ?string $resolvedProjectId = null,
        ?bool $isTestModeEnabled = null,
        ?bool $enableNumberField = null,
        bool $isSinglePerOrderShipmentEnabled = false
    ) {
        $this->projectId = $projectId !== null ? (int)$projectId : null;
        $this->userAgent = $userAgent;
        $this->resolvedProjectId = $resolvedProjectId;
        $this->projectPassword = $projectPassword;
        $this->isTestModeEnabled = $isTestModeEnabled;
        $this->enableNumberField = $enableNumberField;
        $this->isSinglePerOrderShipmentEnabled = $isSinglePerOrderShipmentEnabled;
    }

    public function getProjectId(): ?int
    {
        return $this->projectId;
    }

    public function getResolvedProjectId(): ?string
    {
        return $this->resolvedProjectId;
    }

    public function getProjectPassword(): ?string
    {
        return $this->projectPassword;
    }

    public function isTestModeEnabled(): ?bool
    {
        return $this->isTestModeEnabled;
    }

    public function isHouseNumberFieldEnabled(): ?bool
    {
        return $this->enableNumberField;
    }

    public function getUserAgent(): string
    {
        return $this->userAgent;
    }

    public function isSinglePerOrderShipmentEnabled(): bool
    {
        return $this->isSinglePerOrderShipmentEnabled;
    }

    public function getDefaultParcelWidth(): int
    {
        return 0;
    }

    public function getDefaultParcelHeight(): int
    {
        return 0;
    }

    public function getDefaultParcelLength(): int
    {
        return 0;
    }

    public function getDefaultParcelWeight(): int
    {
        return 0;
    }
}
