<?php

declare(strict_types=1);

namespace Paysera\Dto;

use Paysera\Scoped\Paysera\DeliverySdk\Entity\PayseraDeliverySettingsInterface;

class PayseraSettingsValidationDto implements PayseraDeliverySettingsInterface
{
    private ?int $projectId;
    private ?string $projectPassword;
    private ?string $resolvedProjectId;
    private ?bool $testMode;
    private ?bool $enableNumberField;
    private string $userAgent;

    public function __construct(
        ?string $projectId = null,
        ?string $projectPassword = null,
        string $userAgent = '',
        ?string $resolvedProjectId = null,
        ?bool $testMode = null,
        ?bool $enableNumberField = null
    )
    {
        $this->projectId = $projectId !== null ? (int)$projectId : null;
        $this->userAgent = $userAgent;
        $this->resolvedProjectId = $resolvedProjectId;
        $this->projectPassword = $projectPassword;
        $this->testMode = $testMode;
        $this->enableNumberField = $enableNumberField ?? false;
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
        return $this->testMode;
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
        return false;
    }
}
