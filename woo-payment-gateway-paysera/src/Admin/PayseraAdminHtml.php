<?php

declare(strict_types=1);

namespace Paysera\Admin;

defined('ABSPATH') || exit;

use Paysera\Entity\PayseraPaths;
use Paysera\Helper\LogHelper;
use Paysera\Helper\PayseraHTMLHelper;

class PayseraAdminHtml
{
    public function buildAboutPage(): string
    {
        PayseraHTMLHelper::enqueueCSS('paysera-delivery-css', PayseraPaths::PAYSERA_DELIVERY_CSS);

        return '<div class="paysera-delivery-about-container"><div class="paysera-delivery-logo-container">'
            . '<img src="' . PayseraPaths::PAYSERA_LOGO . '" alt="paysera-logo"/></div>'
            . '<h1>' . __('About Paysera', PayseraPaths::PAYSERA_TRANSLATIONS) . '</h1><p>'
            . sprintf(
                // translators: %s: Paysera link
                __('%s is a global fintech company providing financial and related services to clients from all over the world since 2004.', PayseraPaths::PAYSERA_TRANSLATIONS),
                '<a href="' . __('https://www.paysera.com/v2/en-GB/paysera-account', PayseraPaths::PAYSERA_TRANSLATIONS) . '" target="_blank" rel="noopener noreferrer">Paysera</a>'
            ) . '</p><h1>' . __('Getting started', PayseraPaths::PAYSERA_TRANSLATIONS) . '</h1>'
            . '<p>' . __('In order to receive full benefits of both Paysera Payment and Delivery plugins, please use the outlined links to access our detailed how-to instructions.', PayseraPaths::PAYSERA_TRANSLATIONS) . '</p>'
            . '<div class="paysera-delivery-inline-container-wrapper"><div class="paysera-delivery-inline-container">'
            . '<h2>' . __('Paysera Payments', PayseraPaths::PAYSERA_TRANSLATIONS) . '</h2></div>'
            . '<div class="paysera-delivery-inline-container">'
            . '<h2>' . __('Paysera Delivery', PayseraPaths::PAYSERA_TRANSLATIONS) . '</h2></div></div>'
            . '<div class="paysera-delivery-inline-container-wrapper"><div class="paysera-delivery-inline-container">'
            . '<p>' . __('This plugin enables you to accept online payments via cards, SMS, or the most popular banks in your country. It is easy to install and is used by thousands of online merchants across Europe.', PayseraPaths::PAYSERA_TRANSLATIONS) . '</p>'
            . '</div><div class="paysera-delivery-inline-container">'
            . '<p>' . __('This plugin displays several different delivery companies that your buyers can choose from when ordering your products. No need to sign separate agreements with the courier companies – we have done it for you. Enjoy low delivery prices and quick support when needed.', PayseraPaths::PAYSERA_TRANSLATIONS) . '</p>'
            . '</div></div><div class="paysera-delivery-inline-container-wrapper"><div class="paysera-delivery-inline-container">'
            . '<p class="paysera-delivery-small-paragraph"><a href="' . __('https://www.paysera.com/v2/en-GB/payment-gateway-checkout', PayseraPaths::PAYSERA_TRANSLATIONS)
            . '" target="_blank" rel="noopener noreferrer">' . __('Read more about it >', PayseraPaths::PAYSERA_TRANSLATIONS) . '</a></p>'
            . '<p class="paysera-delivery-small-paragraph"><a href="' . __('https://developers.paysera.com/en/checkout/basic', PayseraPaths::PAYSERA_TRANSLATIONS)
            . '" target="_blank" rel="noopener noreferrer">' . __('Instructions >', PayseraPaths::PAYSERA_TRANSLATIONS) . '</a></p>'
            . '</div><div class="paysera-delivery-inline-container">'
            . '<p class="paysera-delivery-small-paragraph"><a href="' . __('https://www.paysera.com/v2/en-GB/checkout-delivery-service', PayseraPaths::PAYSERA_TRANSLATIONS)
            . '" target="_blank" rel="noopener noreferrer">' . __('Read more about it >', PayseraPaths::PAYSERA_TRANSLATIONS) . '</a></p>'
            . '<p class="paysera-delivery-small-paragraph"><a href="' . __('https://developers.paysera.com/en/delivery', PayseraPaths::PAYSERA_TRANSLATIONS)
            . '" target="_blank" rel="noopener noreferrer">' . __('Instructions >', PayseraPaths::PAYSERA_TRANSLATIONS) . '</a></p>'
            . '</div></div><h1>' . __('Need assistance?', PayseraPaths::PAYSERA_TRANSLATIONS) . '</h1>'
            . '<p>' . __('Paysera client support in English is available 24/7!', PayseraPaths::PAYSERA_TRANSLATIONS) . '</p>'
            . '<p>+44 20 80996963</br>support@paysera.com</p>'
            . '<p>' . __('During working hours the support is available in 12 languages.', PayseraPaths::PAYSERA_TRANSLATIONS) . '</br>'
            . '<a href="' . __('https://www.paysera.com/v2/en-GB/contacts', PayseraPaths::PAYSERA_TRANSLATIONS)
            . '" target="_blank" rel="noopener noreferrer">' . __('Contact us >', PayseraPaths::PAYSERA_TRANSLATIONS) . '</a></p>'
            . '<p>' . sprintf(
                // translators: 1: Facebook link, 2: Twitter link
                __('For the latest news about the Paysera services and status updates – follow us on %1$s and %2$s.', PayseraPaths::PAYSERA_TRANSLATIONS),
                '<a href="https://www.facebook.com/paysera.international/" target="_blank" rel="noopener noreferrer">Facebook</a>',
                '<a href="https://twitter.com/paysera" target="_blank" rel="noopener noreferrer">Twitter</a>'
            ) . '</p>'
            . '<h1>' . __('Explore other services of Paysera', PayseraPaths::PAYSERA_TRANSLATIONS) . '</h1>'
            . '<p>' . __('Alongside its\' most popular services, Paysera also offers:', PayseraPaths::PAYSERA_TRANSLATIONS) . '</p>'
            . '<ul><li><p class="paysera-delivery-small-paragraph">' . __('Currency exchange at competitive rates', PayseraPaths::PAYSERA_TRANSLATIONS) . '</p></li>'
            . '<li><p class="paysera-delivery-small-paragraph">' . __('Enables instant Euro and cheap international transfers', PayseraPaths::PAYSERA_TRANSLATIONS) . '</p></li>'
            . '<li><p class="paysera-delivery-small-paragraph">' . sprintf(
                // translators: %s: IBAN link
                __('Provides LT, BGN, and RON %s for business and private clients', PayseraPaths::PAYSERA_TRANSLATIONS),
                '<a href="' . __('https://www.paysera.com/v2/en/blog/iban-account', PayseraPaths::PAYSERA_TRANSLATIONS)
                . '" target="_blank" rel="noopener noreferrer">IBAN</a>'
            ) . '</p></li>'
            . '<li><p class="paysera-delivery-small-paragraph">' . sprintf(
                // translators: 1: Visa cards link, 2: Google Pay link, 3: Apple Pay link
                __('Issues %1$s that are compatible with %2$s and %3$s, and so much more.', PayseraPaths::PAYSERA_TRANSLATIONS),
                '<a href="' . __('https://www.paysera.com/v2/en-GB/payment-card-visa', PayseraPaths::PAYSERA_TRANSLATIONS) . '" target="_blank" rel="noopener noreferrer">'
                . __('Visa cards', PayseraPaths::PAYSERA_TRANSLATIONS) . '</a>',
                '<a href="' . __('https://www.paysera.com/v2/en-GB/blog/googlepay-samsungpay', PayseraPaths::PAYSERA_TRANSLATIONS) . '" target="_blank" rel="noopener noreferrer">Google Pay</a>',
                '<a href="' . __('https://www.paysera.com/v2/en-GB/apple-pay', PayseraPaths::PAYSERA_TRANSLATIONS) . '" target="_blank" rel="noopener noreferrer">Apple Pay</a>'
            ) . '</p></li>'
            . '</ul><p>' . sprintf(
                // translators: %s: Paysera link
                __('All the main services can be easily managed via the %s which is available for download from the App Store, Google Play, and Huawei AppGallery.', PayseraPaths::PAYSERA_TRANSLATIONS),
                '<a href="' . __('https://www.paysera.com/v2/en-GB/mobile-application', PayseraPaths::PAYSERA_TRANSLATIONS) . '" target="_blank" rel="noopener noreferrer">'
                . __('Paysera mobile app', PayseraPaths::PAYSERA_TRANSLATIONS) . '</a>'
            ) . '</p>'
            . '<p>' . __('Thank you for choosing our services!', PayseraPaths::PAYSERA_TRANSLATIONS) . '</p></div>';
    }

    public function getTextInput(bool $isRequired = false): string
    {
        return '<input class="paysera-settings-input" type="text" name="%s" value="%s" ' . (($isRequired) ? 'required' : '') . '/>';
    }

    public function getNumberInput(bool $isRequired = false): string
    {
        return '<input class="paysera-settings-input" type="number" name="%s" value="%s" min="%d" ' . (($isRequired) ? 'required' : '') . '/>';
    }

    public function getTextAreaInput(): string
    {
        return '<textarea class="paysera-settings-input" type="number" name="%s">%s</textarea>';
    }

    public function getMultipleSelectInput(array $options, array $selected): string
    {
        $html = '<select class="paysera-multiple-select" multiple="multiple" name="%s[]">';

        foreach ($options as $key => $option) {
            $html .= '<option value="' . $key . '" ' . ((in_array($key, $selected, true) === true) ? 'selected' : '')
                . '>' . $option . '</option>'
            ;
        }

        $html .= '</select>';

        return $html;
    }

    public function getSelectInput(array $options, string $selected, bool $isSingleSelect = false): string
    {
        $html = sprintf(
            '<select class="%s" name="%%s%s">',
            $isSingleSelect ? 'paysera-single-select' : 'paysera-multiple-select',
            $isSingleSelect ? '' : '[]'
        );

        foreach ($options as $value => $option) {
            $html .= '<option value="' . $value . '" ' . (($value === $selected) ? 'selected' : '') . '>' . $option
                . '</option>'
            ;
        }

        $html .= '</select>';

        return $html;
    }

    public function getConfigurableSelectInput(array $options, string $selected, array $configuration): string
    {
        $attributes = [];

        foreach ($configuration as $key => $value) {
            $attributes[] = sprintf('%s="%s"', $key, $value);
        }

        $html = sprintf(
            '<select class="paysera-configurable-select2" name="%%s" %s><option></option>',
            implode(' ', $attributes)
        );

        foreach ($options as $value => $option) {
            $html .= sprintf(
                '<option value="%s" %s>%s</option>',
                $value,
                ($value === $selected) ? 'selected' : '',
                $option
            );
        }

        $html .= '</select>';

        return $html;
    }

    public function getEnableInput(string $parametersName, string $parameterValue): string
    {
        return '<select name="' . $parametersName . '">' . '<option value="yes">'
            . __('Enabled', PayseraPaths::PAYSERA_TRANSLATIONS) . '</option>' . '<option value="no" '
            . (($parameterValue === 'no') ? 'selected' : '') . '>' . __('Disabled', PayseraPaths::PAYSERA_TRANSLATIONS)
            . '</option></select>'
        ;
    }

    public function getSettingsSavedMessage(): string
    {
        return '<div class="updated"><p>' . __('Settings saved.', PayseraPaths::PAYSERA_TRANSLATIONS) . '</p></div>';
    }

    public function getSettingsWarningNotice(): string
    {
        return '<div class="update-message notice inline notice-error notice-alt"><p>'
            . __('The project ID and password fields must be filled with valid data', PayseraPaths::PAYSERA_TRANSLATIONS) . '</p></div>'
        ;
    }

    public function getSettingsInvalidCredentialsNotice(): string
    {
        return '<div class="update-message notice inline notice-error notice-alt"><p>'
            . sprintf(
                // translators: %s: Paysera link
                __(
                    'Incorrect project ID and password, please check your project credentials, %s',
                    PayseraPaths::PAYSERA_TRANSLATIONS
                ),
                '<a href="' . __('https://support.paysera.com/index.php?/payseraeng/Knowledgebase/Article/View/1815/135/1005-project-id-and-signature', PayseraPaths::PAYSERA_TRANSLATIONS) . '" target="_blank">'
                . __('more', PayseraPaths::PAYSERA_TRANSLATIONS) . '</a>'
            )
            . '</p></div>'
        ;
    }

    public function buildLabel(string $label): string
    {
        return '<p class="description">' . $label . '</p>';
    }

    public function getLinkButton(string $text, string $link, bool $openInNewTab = false): string
    {
        return sprintf(
            '<a href="%s" class="button button-secondary" target="%s">%s</a>',
            $link,
            $openInNewTab ? '_blank' : '_self',
            $text
        );
    }

    public function getLogLevelHtml(
        string $name,
        string $selectedOption,
        string $loggerType,
        LogHelper $logHelper
    ): string {
        $html = sprintf(
            $this->getSelectInput(
                $logHelper->getLogLevelSelectOptions(),
                $selectedOption,
                true
            ),
            $name
        );

        if ($logHelper->isLogFilesAvailable($loggerType)) {
            $html .= '&nbsp;' . $this->getLinkButton(
                __('View', PayseraPaths::PAYSERA_TRANSLATIONS),
                admin_url('admin.php?page=wc-status&tab=logs'),
                true
            );
        }

        if ($logHelper->isZipArchivable($loggerType)) {
            $html .= '&nbsp;' . $this->getLinkButton(
                __('Download', PayseraPaths::PAYSERA_TRANSLATIONS),
                wp_nonce_url(
                    admin_url(sprintf(
                        'admin-post.php?action=paysera_log_archive_download&logger_type=%s',
                        $loggerType
                    )),
                    'paysera_download_logs')
            );
        }

        $html .= $this->buildLabel(__(
            'This will save Paysera plugin logs in WooCommerce',
            PayseraPaths::PAYSERA_TRANSLATIONS
        ));

        return $html;
    }

    public function getSettingsCompatibilityValidationErrorNotice(): string
    {
        return '<div class="update-message notice inline notice-error notice-alt"><p>'
            . sprintf(
                __(
                    'This setting cannot be enabled due to compatibility issues. Please visit the <a href="%s" target="_blank">diagnostic page</a> to review and resolve them.',
                    PayseraPaths::PAYSERA_TRANSLATIONS
                ),
                admin_url('admin.php?page=paysera-self-diagnosis', admin_url())
            )
            . '</p></div>'
            ;
    }
}
