<?php

declare(strict_types=1);

namespace Paysera\Provider;

defined('ABSPATH') || exit;

use Paysera\Entity\PayseraPaths;

class DeliveryNoticeMessageProvider
{
    public function getEmptyCredentialsMessage(): string
    {
        return $this->getInvalidCredentialsMessage();
    }

    public function getInvalidCredentialsMessage(): string
    {
        return sprintf(
            __(
                'Incorrect project ID and password, please check your project credentials, %s',
                PayseraPaths::PAYSERA_TRANSLATIONS
            ),
            $this->getSupportLink()
        );
    }

    public function getValidationFailedMessage(string $reason): string
    {
        return __('We couldn\'t verify your credentials. ', PayseraPaths::PAYSERA_TRANSLATIONS) . esc_html($reason);
    }

    public function getMerchantNotFoundMessage(): string
    {
        return __('Merchant client not found. Contact support.', PayseraPaths::PAYSERA_TRANSLATIONS);
    }

    public function getRateLimitExceededMessage(): string
    {
        return __(
            'Rate limit exceeded – too many validation attempts. Wait and try again.',
            PayseraPaths::PAYSERA_TRANSLATIONS
        );
    }

    public function getValidationErrorMessage(): string
    {
        return __('Credential validation error. Try again or contact support.', PayseraPaths::PAYSERA_TRANSLATIONS);
    }

    public function getEnableDeliveryWithoutCredentialsMessage(): string
    {
        return __(
            'Enter your Project ID and Password to enable Paysera Delivery.',
            PayseraPaths::PAYSERA_TRANSLATIONS
        );
    }

    private function getSupportLink(): string
    {
        return sprintf(
            '<a href="%s" target="_blank">%s</a>',
            __(
                'https://support.paysera.com/index.php?/payseraeng/Knowledgebase/Article/View/1815/135/1005-project-id-and-signature',
                PayseraPaths::PAYSERA_TRANSLATIONS
            ),
            __('Learn more.', PayseraPaths::PAYSERA_TRANSLATIONS)
        );
    }
}
