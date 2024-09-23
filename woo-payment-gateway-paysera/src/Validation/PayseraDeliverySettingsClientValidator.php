<?php

declare(strict_types=1);

namespace Paysera\Validation;

use Paysera\Entity\PayseraDeliverySettings;
use Paysera\Entity\PayseraPaths;
use Paysera\PayseraInit;

class PayseraDeliverySettingsClientValidator
{
    private array $options = [
        PayseraDeliverySettings::OPTION_DECIMAL_SEPARATOR => '.',
    ];

    public function __construct(
        array $fieldsTitles,
        array $errorsTemplates,
        array $ruleSet,
        array $options = []
    ) {
        $this->options = array_merge($this->options, $options);

        if (is_admin()) {
            wp_enqueue_style('paysera-delivery-css', PayseraPaths::PAYSERA_ADMIN_DELIVERY_SETTINGS_CSS);
            wp_register_script('delievery-settings-admin', PayseraPaths::PAYSERA_ADMIN_DELIVERY_SETTINGS_JS, ['jquery'], false);
            wp_enqueue_script('delievery-settings-admin');
        }

        wp_localize_script(
            'delievery-settings-admin',
            'paysera_delivery_settings_admin',
            [
                'fieldNames' => $fieldsTitles,
                'errors' => $errorsTemplates,
                'ruleSet' => $ruleSet,
                'options' => $this->options,
            ]
        );
    }

    public function generateValidatableField(string $key, array $settings, \Paysera_Delivery_Gateway $gateway): string
    {
        $fieldKey = $gateway->get_field_key($key);
        $settings = wp_parse_args($settings, $this->getDefaultFieldSettings());

        ob_start(); ?>
        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for="<?php echo esc_attr($fieldKey); ?>">
                    <?php echo wp_kses_post($settings['title']); ?> <?php echo $gateway->get_tooltip_html($settings); ?>
                </label>
            </th>
            <td class="forminp">
                <fieldset>
                    <script>
                        jQuery(document).ready(function ($) {
                            $('#<?= esc_attr($fieldKey); ?>').on('input', function () {
                                $(document.body).triggerHandler('paysera_delivery_settings_validation')
                            });
                        });
                    </script>

                    <legend class="screen-reader-text"><span><?php echo wp_kses_post($settings['title']); ?></span></legend>
                    <input
                        class="paysera-delivery-input input-text regular-input <?php echo esc_attr($settings['class']); ?>"
                        type="text"
                        name="<?php echo esc_attr($fieldKey); ?>"
                        id="<?php echo esc_attr($fieldKey); ?>"
                        style="<?php echo esc_attr($settings['css']); ?>"
                        value="<?php echo esc_attr(wc_format_localized_decimal($gateway->get_option($key))); ?>"
                        placeholder="<?php echo esc_attr($settings['placeholder']); ?>"
                        data-name="<?php echo $key; ?>"
                        <?php disabled($settings['disabled'], true); ?>
                        <?php echo $gateway->get_custom_attribute_html($settings); ?>
                    />
                    <?php echo $gateway->get_description_html($settings); // WPCS: XSS ok.?>
                </fieldset>
            </td>
        </tr>
        <?php

        return ob_get_clean();
    }

    private function getDefaultFieldSettings(): array
    {
        return [
            'title' => '',
            'disabled' => false,
            'class' => '',
            'css' => '',
            'placeholder' => '',
            'type' => 'text',
            'desc_tip' => false,
            'description' => '',
            'custom_attributes' => [],
        ];
    }
}
