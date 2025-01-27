<?php

declare(strict_types=1);

namespace Paysera\Factory;

use Paysera\DataValidator\Validator\Exception\IncorrectValidationRuleStructure;
use Paysera\Validation\PayseraDeliverySettingsClientValidator;
use Paysera\Validation\PayseraDeliverySettingsValidator;
use Paysera_Delivery_Gateway;

class DeliverySettingsValidatorFactory
{
    /**
     * @throws IncorrectValidationRuleStructure
     */
    public function createBackendValidator(Paysera_Delivery_Gateway $deliveryGateway): PayseraDeliverySettingsValidator
    {
        return new PayseraDeliverySettingsValidator(
            $deliveryGateway->getValidationFieldsTitles(),
            $deliveryGateway->getValidationErrorsTemplates(),
            $deliveryGateway->getValidationRules(),
            $deliveryGateway->getValidationOptions(),
        );
    }

    public function createClientValidator(
        Paysera_Delivery_Gateway $deliveryGateway
    ): PayseraDeliverySettingsClientValidator {
        return new PayseraDeliverySettingsClientValidator(
            $deliveryGateway->getValidationFieldsTitles(),
            $deliveryGateway->getValidationErrorsTemplates(),
            $deliveryGateway->getValidationRules(),
            $deliveryGateway->getValidationOptions(),
        );
    }
}
