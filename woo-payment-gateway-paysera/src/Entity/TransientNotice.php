<?php

declare(strict_types=1);

namespace Paysera\Entity;

defined('ABSPATH') || exit;

use InvalidArgumentException;

class TransientNotice
{
    public const TYPE_SUCCESS = 'success';
    public const TYPE_WARNING = 'warning';
    public const TYPE_ERROR = 'error';

    private string $type;
    private string $message;
    private ?string $context;
    private int $timestamp;

    public function __construct(
        string $type,
        string $message,
        ?string $context = null,
        ?int $timestamp = null
    ) {
        $this->validateType($type);
        $this->type = $type;
        $this->message = $message;
        $this->context = $context;
        $this->timestamp = $timestamp ?? time();
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getContext(): ?string
    {
        return $this->context;
    }

    public function getTimestamp(): int
    {
        return $this->timestamp;
    }

    public function isSuccess(): bool
    {
        return $this->type === self::TYPE_SUCCESS;
    }

    public function isWarning(): bool
    {
        return $this->type === self::TYPE_WARNING;
    }

    public function isError(): bool
    {
        return $this->type === self::TYPE_ERROR;
    }

    public function getAge(): int
    {
        return time() - $this->timestamp;
    }

    public function isExpired(int $ttl = 45): bool
    {
        return $this->getAge() > $ttl;
    }

    /**
     * @return array{type: string, message: string, context: string|null, timestamp: int}
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'message' => $this->message,
            'context' => $this->context,
            'timestamp' => $this->timestamp,
        ];
    }

    private function validateType(string $type): void
    {
        $validTypes = [self::TYPE_SUCCESS, self::TYPE_WARNING, self::TYPE_ERROR];
        if (!in_array($type, $validTypes, true)) {
            throw new InvalidArgumentException('Invalid notice type: ' . $type);
        }
    }
}
