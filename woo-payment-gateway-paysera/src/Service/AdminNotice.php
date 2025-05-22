<?php

declare(strict_types=1);

namespace Paysera\Service;

class AdminNotice
{
    public const NOTICE_TYPE_ERROR = 'error';
    private const NOTICE_TRANSIENT_KEY = 'paysera_admin_notices';
    private const NOTICE_DISMISS_KEY = 'paysera_dismiss_notice';

    private array $notices;

    public function __construct()
    {
        $this->loadNotices();
        $this->removeDismissedNotice();

        add_action('admin_notices', [$this, 'showNotices']);
    }

    private function loadNotices(): void
    {
        $notices = get_transient(self::NOTICE_TRANSIENT_KEY);
        $this->notices = $notices === false ? [] : $notices;
    }

    private function removeDismissedNotice(): void
    {
        if (isset($_GET[self::NOTICE_DISMISS_KEY]) === false) {
            return;
        }

        $dismissedNoticeIndex = (int)$_GET[self::NOTICE_DISMISS_KEY];

        if (isset($this->notices[$dismissedNoticeIndex])) {
            unset($this->notices[$dismissedNoticeIndex]);
            set_transient(self::NOTICE_TRANSIENT_KEY, $this->notices, 0);
        }
    }

    public function showNotices(): void
    {
        if (
                is_admin() === false
                || current_user_can('manage_options') === false
                || empty($this->notices)
        ) {
            return;
        }

        foreach ($this->notices as $index => $notice) {
            ?>
            <div class="notice notice-<?php echo esc_attr($notice['type']); ?> is-dismissible">
                <a class="notice-dismiss" href="<?php echo $this->getDismissUrl($index) ?>" style="text-decoration: none"></a>
                <p><?php echo ($notice['message']); ?></p>
            </div>
            <?php
        }
    }

    private function getDismissUrl(int $index): string
    {
        return add_query_arg(self::NOTICE_DISMISS_KEY, $index);
    }

    public function addErrorNotice(string $message, string $type = self::NOTICE_TYPE_ERROR)
    {
        if (in_array($message, array_column($this->notices, 'message'))) {
            return;
        }

        $this->notices[] = [
            'message' => $message,
            'type' => $type,
        ];

        set_transient(self::NOTICE_TRANSIENT_KEY, $this->notices, 0);
    }
}
