<?php

declare(strict_types=1);

namespace Paysera\Service;

defined('ABSPATH') || exit;

use Paysera\Entity\TransientNotice;

class TransientNoticeRenderer
{
    public function render(TransientNotice $notice): string
    {
        $noticeClass = $this->getNoticeClass($notice);

        return sprintf(
            '<div class="update-message notice inline %s notice-alt"><p>%s</p></div>',
            esc_attr($noticeClass),
            wp_kses($notice->getMessage(), [
                'a' => [
                    'href' => true,
                    'title' => true,
                    'target' => true,
                    'rel' => true,
                ],
                'b' => [],
                'strong' => [],
                'i' => [],
                'em' => [],
                'u' => [],
                'br' => [],
            ])
        );
    }

    private function getNoticeClass(TransientNotice $notice): string
    {
        if ($notice->isSuccess()) {
            return 'notice-success';
        }

        if ($notice->isWarning()) {
            return 'notice-warning';
        }

        return 'notice-error';
    }
}
