<?php

declare(strict_types=1);

namespace Paysera\Factory;

defined('ABSPATH') || exit;

use InvalidArgumentException;
use Paysera\Entity\TransientNotice;

class TransientNoticeFactory
{
    private ?string $type;
    private ?string $message;
    private ?string $context;

    public function __construct(?string $type = null, ?string $message = null, ?string $context = null)
    {
        $this->type = $type;
        $this->message = $message;
        $this->context = $context;
    }

    /**
     * @param array{type: string, message: string, context?: string|null, timestamp?: int} $data
     */
    public function createFromArray(array $data): ?TransientNotice
    {
        if (!isset($data['type']) || !isset($data['message'])) {
            return null;
        }

        return new TransientNotice(
            $data['type'],
            $data['message'],
            $data['context'] ?? null,
            $data['timestamp'] ?? null
        );
    }

    public function error(string $message): self
    {
        return new self(TransientNotice::TYPE_ERROR, $message, $this->context);
    }

    public function warning(string $message): self
    {
        return new self(TransientNotice::TYPE_WARNING, $message, $this->context);
    }

    public function success(string $message): self
    {
        return new self(TransientNotice::TYPE_SUCCESS, $message, $this->context);
    }

    public function create(string $type, string $message): self
    {
        return new self($type, $message, $this->context);
    }

    public function withContext(string $context): self
    {
        return new self($this->type, $this->message, $context);
    }

    public function build(): TransientNotice
    {
        if ($this->type === null || $this->type === '') {
            throw new InvalidArgumentException('Notice type must be set before building');
        }

        if ($this->message === null || $this->message === '') {
            throw new InvalidArgumentException('Notice message must be set before building');
        }

        return new TransientNotice(
            $this->type,
            $this->message,
            $this->context
        );
    }
}
