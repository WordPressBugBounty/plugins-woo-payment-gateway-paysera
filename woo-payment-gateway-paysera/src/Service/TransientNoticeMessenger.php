<?php

declare(strict_types=1);

namespace Paysera\Service;

defined('ABSPATH') || exit;

use Paysera\Entity\TransientNotice;
use Paysera\Factory\TransientNoticeFactory;

class TransientNoticeMessenger
{
    private const TRANSIENT_PREFIX = 'paysera_notice_';
    private const TRANSIENT_QUEUE_PREFIX = 'paysera_notice_queue_';
    private const DEFAULT_TTL = 45;
    private const CRITICAL_TTL = 300;
    private const MAX_NOTICES = 10;

    private TransientNoticeFactory $noticeFactory;
    private array $config;

    /**
     * @param array<string,mixed> $config
     */
    public function __construct(
        TransientNoticeFactory $noticeFactory,
        array $config = []
    ) {
        $this->noticeFactory = $noticeFactory;
        $this->config = array_merge([
            'default_ttl' => self::DEFAULT_TTL,
            'error_ttl' => self::CRITICAL_TTL,
            'max_notices' => self::MAX_NOTICES,
            'auto_delete' => true,
        ], $config);
    }

    public function set(TransientNotice $notice, ?int $userId = null, ?int $customTtl = null): void
    {
        $effectiveUserId = $userId ?? get_current_user_id();
        $key = $this->buildKey($effectiveUserId);
        $ttl = $customTtl ?? $this->getTtlForType($notice->getType());

        set_transient($key, $notice->toArray(), $ttl);
    }

    public function get(?int $userId = null, bool $delete = true): ?TransientNotice
    {
        $effectiveUserId = $userId ?? get_current_user_id();
        $key = $this->buildKey($effectiveUserId);

        $data = get_transient($key);

        if (!is_array($data)) {
            return null;
        }

        if ($delete) {
            delete_transient($key);
        }

        return $this->noticeFactory->createFromArray($data);
    }

    public function peek(?int $userId = null): ?TransientNotice
    {
        return $this->get($userId, false);
    }

    public function has(?int $userId = null): bool
    {
        return $this->peek($userId) !== null;
    }

    public function clear(?int $userId = null): void
    {
        $effectiveUserId = $userId ?? get_current_user_id();
        delete_transient($this->buildKey($effectiveUserId));
    }

    public function addToQueue(TransientNotice $notice, ?int $userId = null, ?int $customTtl = null): void
    {
        $notices = $this->getAllFromQueue($userId, false);
        $notices[] = $notice;

        if (count($notices) > $this->config['max_notices']) {
            array_shift($notices);
        }

        $this->saveQueue($notices, $userId, $customTtl);
    }

    /**
     * @return TransientNotice[]
     */
    public function getAllFromQueue(?int $userId = null, bool $delete = true): array
    {
        $effectiveUserId = $userId ?? get_current_user_id();
        $key = $this->buildQueueKey($effectiveUserId);

        $data = get_transient($key);

        if (!is_array($data)) {
            return [];
        }

        if ($delete) {
            delete_transient($key);
        }

        $notices = [];
        foreach ($data as $noticeData) {
            if (is_array($noticeData)) {
                $notice = $this->noticeFactory->createFromArray($noticeData);
                if ($notice !== null) {
                    $notices[] = $notice;
                }
            }
        }

        return $notices;
    }

    /**
     * @return TransientNotice[]
     */
    public function getByContext(string $context, ?int $userId = null, bool $delete = true): array
    {
        $allNotices = $this->getAllFromQueue($userId, false);
        $filtered = array_filter($allNotices, function (TransientNotice $notice) use ($context) {
            return $notice->getContext() === $context;
        });

        if ($delete && count($filtered) > 0) {
            $remaining = array_filter($allNotices, function (TransientNotice $notice) use ($context) {
                return $notice->getContext() !== $context;
            });
            $this->saveQueue(array_values($remaining), $userId);
        }

        return array_values($filtered);
    }

    public function clearQueue(?int $userId = null): void
    {
        $effectiveUserId = $userId ?? get_current_user_id();
        delete_transient($this->buildQueueKey($effectiveUserId));
    }

    /**
     * @param TransientNotice[] $notices
     */
    private function saveQueue(array $notices, ?int $userId = null, ?int $customTtl = null): void
    {
        $effectiveUserId = $userId ?? get_current_user_id();
        $key = $this->buildQueueKey($effectiveUserId);
        $ttl = $customTtl ?? $this->config['default_ttl'];

        $data = array_map(function (TransientNotice $notice) {
            return $notice->toArray();
        }, $notices);

        set_transient($key, $data, $ttl);
    }

    private function getTtlForType(string $type): int
    {
        return $type === TransientNotice::TYPE_ERROR
            ? $this->config['error_ttl']
            : $this->config['default_ttl'];
    }

    private function buildKey(int $userId): string
    {
        return self::TRANSIENT_PREFIX . $userId;
    }

    private function buildQueueKey(int $userId): string
    {
        return self::TRANSIENT_QUEUE_PREFIX . $userId;
    }
}
