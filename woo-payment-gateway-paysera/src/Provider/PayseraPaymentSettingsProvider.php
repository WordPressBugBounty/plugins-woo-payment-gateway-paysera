<?php

declare(strict_types=1);

namespace Paysera\Provider;

defined('ABSPATH') || exit;

use Paysera\Entity\PayseraPaths;
use Paysera\Entity\PayseraPaymentSettings;
use Paysera\Helper\LogHelper;

class PayseraPaymentSettingsProvider
{
    public function getPayseraPaymentSettings(): PayseraPaymentSettings
    {
        $mainOptions = get_option(PayseraPaymentSettings::MAIN_SETTINGS_NAME);
        $extraOptions = get_option(PayseraPaymentSettings::EXTRA_SETTINGS_NAME);
        $statusOptions = get_option(PayseraPaymentSettings::STATUS_SETTINGS_NAME);
        $projectAdditionsOptions = get_option(PayseraPaymentSettings::PROJECT_ADDITIONS_SETTINGS_NAME);

        //ToDo: remove old options after some time
        $oldOptions = get_option('woocommerce_paysera_settings');

        $payseraPaymentSettings = (new PayseraPaymentSettings())
            ->setEnabled(isset($oldOptions['enabled']) && $oldOptions['enabled'] === 'yes')
            ->setTestModeEnabled(false)
            ->setListOfPaymentsEnabled(true)
            ->setGridViewEnabled(false)
            ->setBuyerConsentEnabled(true)
            ->setNewOrderStatus('wc-processing')
            ->setPaidOrderStatus('wc-completed')
            ->setPendingCheckoutStatus('wc-pending')
            ->setOwnershipCodeEnabled(false)
            ->setQualitySignEnabled(false)
            ->setLogLevel(LogHelper::LOG_LEVEL_ERROR)
        ;

        if (isset($mainOptions[PayseraPaymentSettings::PROJECT_ID])) {
            $payseraPaymentSettings->setProjectId((int) trim($mainOptions[PayseraPaymentSettings::PROJECT_ID]));
        } elseif (isset($oldOptions['projectid'])) {
            $payseraPaymentSettings->setProjectId((int) $oldOptions['projectid']);
        }

        if (isset($mainOptions[PayseraPaymentSettings::PROJECT_PASSWORD])) {
            $payseraPaymentSettings->setProjectPassword(trim($mainOptions[PayseraPaymentSettings::PROJECT_PASSWORD]));
        } elseif (isset($oldOptions['password'])) {
            $payseraPaymentSettings->setProjectPassword($oldOptions['password']);
        }

        if (isset($mainOptions[PayseraPaymentSettings::TEST_MODE])) {
            $payseraPaymentSettings->setTestModeEnabled($mainOptions[PayseraPaymentSettings::TEST_MODE] === 'yes');
        } elseif (isset($oldOptions['test'])) {
            $payseraPaymentSettings->setTestModeEnabled($oldOptions['test'] === 'yes');
        }

        if (isset($extraOptions[PayseraPaymentSettings::TITLE])) {
            $payseraPaymentSettings->setTitle($extraOptions[PayseraPaymentSettings::TITLE]);
        } elseif (isset($oldOptions['title'])) {
            $payseraPaymentSettings->setTitle($oldOptions['title']);
        }

        if (isset($extraOptions[PayseraPaymentSettings::DESCRIPTION])) {
            $payseraPaymentSettings->setDescription($extraOptions[PayseraPaymentSettings::DESCRIPTION]);
        } elseif (isset($oldOptions['description'])) {
            $payseraPaymentSettings->setDescription($oldOptions['description']);
        }

        if (isset($extraOptions[PayseraPaymentSettings::LIST_OF_PAYMENTS])) {
            $payseraPaymentSettings->setListOfPaymentsEnabled(
                $extraOptions[PayseraPaymentSettings::LIST_OF_PAYMENTS]
                === 'yes'
            );
        } elseif (isset($oldOptions['paymentType'])) {
            $payseraPaymentSettings->setListOfPaymentsEnabled($oldOptions['paymentType'] === 'yes');
        }

        if (isset($extraOptions[PayseraPaymentSettings::SPECIFIC_COUNTRIES])) {
            $payseraPaymentSettings->setSpecificCountries($extraOptions[PayseraPaymentSettings::SPECIFIC_COUNTRIES]);
        } elseif (isset($oldOptions['countriesSelected'])) {
            $normalizedSpecificCountries = [];

            if ($oldOptions['countriesSelected'] !== '') {
                foreach ($oldOptions['countriesSelected'] as $countryCode) {
                    $normalizedSpecificCountries[] = strtoupper($countryCode);
                }
            }

            $payseraPaymentSettings->setSpecificCountries($normalizedSpecificCountries);
        }

        if (isset($extraOptions[PayseraPaymentSettings::GRID_VIEW])) {
            $payseraPaymentSettings->setGridViewEnabled($extraOptions[PayseraPaymentSettings::GRID_VIEW] === 'yes');
        } elseif (isset($oldOptions['style'])) {
            $payseraPaymentSettings->setGridViewEnabled($oldOptions['style'] === 'yes');
        }

        if (isset($extraOptions[PayseraPaymentSettings::BUYER_CONSENT])) {
            $payseraPaymentSettings->setBuyerConsentEnabled(
                $extraOptions[PayseraPaymentSettings::BUYER_CONSENT]
                === 'yes'
            );
        } elseif (isset($oldOptions['buyerConsent'])) {
            $payseraPaymentSettings->setBuyerConsentEnabled($oldOptions['buyerConsent'] === 'yes');
        }

        if (isset($statusOptions[PayseraPaymentSettings::NEW_ORDER_STATUS])) {
            $payseraPaymentSettings->setNewOrderStatus($statusOptions[PayseraPaymentSettings::NEW_ORDER_STATUS][0]);
        } elseif (isset($oldOptions['paymentNewOrderStatus'])) {
            $payseraPaymentSettings->setNewOrderStatus($oldOptions['paymentNewOrderStatus']);
        }

        if (isset($statusOptions[PayseraPaymentSettings::PAID_ORDER_STATUS])) {
            $payseraPaymentSettings->setPaidOrderStatus($statusOptions[PayseraPaymentSettings::PAID_ORDER_STATUS][0]);
        } elseif (isset($oldOptions['paymentCompletedStatus'])) {
            $payseraPaymentSettings->setPaidOrderStatus($oldOptions['paymentCompletedStatus']);
        }

        if (isset($statusOptions[PayseraPaymentSettings::PENDING_CHECKOUT_STATUS])) {
            $payseraPaymentSettings->setPendingCheckoutStatus(
                $statusOptions[PayseraPaymentSettings::PENDING_CHECKOUT_STATUS][0]
            );
        } elseif (isset($oldOptions['paymentPendingStatus'])) {
            $payseraPaymentSettings->setPendingCheckoutStatus($oldOptions['paymentPendingStatus']);
        }

        if (isset($projectAdditionsOptions[PayseraPaymentSettings::OWNERSHIP_CODE_ENABLED])) {
            $payseraPaymentSettings->setOwnershipCodeEnabled(
                $projectAdditionsOptions[PayseraPaymentSettings::OWNERSHIP_CODE_ENABLED]
                === 'yes'
            );
        } elseif (isset($oldOptions['enableOwnershipCode'])) {
            $payseraPaymentSettings->setOwnershipCodeEnabled($oldOptions['enableOwnershipCode'] === 'yes');
        }

        if (isset($projectAdditionsOptions[PayseraPaymentSettings::OWNERSHIP_CODE])) {
            $payseraPaymentSettings->setOwnershipCode($projectAdditionsOptions[PayseraPaymentSettings::OWNERSHIP_CODE]);
        } elseif (isset($oldOptions['ownershipCode'])) {
            $payseraPaymentSettings->setOwnershipCode($oldOptions['ownershipCode']);
        }

        if (isset($projectAdditionsOptions[PayseraPaymentSettings::QUALITY_SIGN_ENABLED])) {
            $payseraPaymentSettings->setQualitySignEnabled(
                $projectAdditionsOptions[PayseraPaymentSettings::QUALITY_SIGN_ENABLED]
                === 'yes'
            );
        } elseif (isset($oldOptions['enableQualitySign'])) {
            $payseraPaymentSettings->setQualitySignEnabled($oldOptions['enableQualitySign'] === 'yes');
        }

        if (isset($extraOptions[PayseraPaymentSettings::LOG_LEVEL])) {
            $payseraPaymentSettings->setLogLevel($extraOptions[PayseraPaymentSettings::LOG_LEVEL]);
        }

        return $payseraPaymentSettings;
    }
}
