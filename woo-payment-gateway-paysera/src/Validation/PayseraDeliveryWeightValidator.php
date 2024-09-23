<?php

declare(strict_types=1);

namespace Paysera\Validation;

use Paysera\DataValidator\Validator\AbstractValidator;
use Paysera\Entity\PayseraPaths;
use Paysera\Helper\PayseraDeliveryHelper;
use Paysera\Helper\SessionHelperInterface;
use Paysera\Provider\PayseraDeliverySettingsProvider;
use Paysera\Validation\Rules\AbstractComparison;
use Paysera\Validation\Rules\GreaterOrEquals;
use Paysera\Validation\Rules\LessOrEquals;

class PayseraDeliveryWeightValidator extends AbstractValidator
{
    private const MESSAGE_GTE = 'Sorry, %d kg is not enough for the minimum weight of %d kg for %s';
    private const MESSAGE_LTE = 'Sorry, %d kg exceeds the maximum weight of %d kg for %s';

    private SessionHelperInterface $sessionHelper;
    private PayseraDeliveryHelper $payseraDeliveryHelper;
    private PayseraDeliverySettingsProvider $payseraDeliverySettingsProvider;

    private float $minimumWeight = 0;
    private float $maximumWeight = 0;
    private float $totalWeight = 0;

    public function __construct(
        SessionHelperInterface $sessionHelper,
        PayseraDeliveryHelper $deliveryHelper,
        PayseraDeliverySettingsProvider $payseraDeliverySettingsProvider
    ) {
        parent::__construct();
        $this->sessionHelper = $sessionHelper;
        $this->payseraDeliveryHelper = $deliveryHelper;
        $this->payseraDeliverySettingsProvider = $payseraDeliverySettingsProvider;
    }

    /**
     * @return array{'validated': bool, 'messages': array}
     */
    public function validateWeight(): array
    {
        return $this->validateShippingMethod(
            $this->payseraDeliveryHelper->getCartTotalDeliveryWeight(),
            $this->getChosenShippingMethod()
        );
    }

    /**
     * @return array{'validated': bool, 'messages': array}
     */
    public function validateShippingMethod(float $totalWeight, string $shippingMethod = null): array
    {
        $this->clearErrors();

        if ($shippingMethod === null) {
            return [
                'validated' => true,
                'messages' => [],
            ];
        }

        $deliveryGateway = $this->payseraDeliveryHelper->extractDeliveryGatewayDataFromShippingMethod($shippingMethod);

        $payseraDeliveryGatewaySettings = $this->payseraDeliverySettingsProvider->getPayseraDeliveryGatewaySettings(
            $deliveryGateway['code'],
            $deliveryGateway['instanceId']
        );

        $this->minimumWeight = $payseraDeliveryGatewaySettings->getMinimumWeight();
        $this->maximumWeight = $payseraDeliveryGatewaySettings->getMaximumWeight();
        $this->totalWeight = $totalWeight;

        $messages = [];

        $this->configureRules($deliveryGateway);

        $data = [
            'min_weight' => $this->minimumWeight,
            'max_weight' => $this->maximumWeight,
            'weight' => $this->totalWeight,
        ];

        if (!$this->validate($data, $this->getRuleSet())) {
            $errors = $this->getProcessedErrors();
            foreach ($errors as $error) {
                $messages[] = __(current($error), PayseraPaths::PAYSERA_TRANSLATIONS);
            }
        }

        return [
            'validated' => count($messages) === 0,
            'messages' => $messages,
        ];
    }

    private function getChosenShippingMethod(): ?string
    {
        $chosenMethods = $this->sessionHelper->getData('chosen_shipping_methods');

        if (!is_array($chosenMethods) || count($chosenMethods) === 0 || !isset($chosenMethods[0])) {
            return null;
        }

        if (!$this->payseraDeliveryHelper->isPayseraDeliveryGateway($chosenMethods[0])) {
            return null;
        }

        return $chosenMethods[0];
    }

    private function configureRules(array $deliveryGateway): void
    {
        $deliveryGatewayCode = $this->payseraDeliveryHelper->resolveDeliveryGatewayCode($deliveryGateway['code']);
        $deliveryGatewayTitle = $this->payseraDeliverySettingsProvider
            ->getPayseraDeliverySettings()
            ->getDeliveryGatewayTitles()[$deliveryGatewayCode];

        $message = sprintf(
            __(self::MESSAGE_GTE, PayseraPaths::PAYSERA_TRANSLATIONS),
            $this->totalWeight,
            $this->minimumWeight,
            $deliveryGatewayTitle
        );
        $this->configureRule(new GreaterOrEquals(), $message);

        $message = sprintf(
            __(self::MESSAGE_LTE, PayseraPaths::PAYSERA_TRANSLATIONS),
            $this->totalWeight,
            $this->maximumWeight,
            $deliveryGatewayTitle
        );
        $this->configureRule(new LessOrEquals(), $message);
    }

    private function configureRule(AbstractComparison $rule, string $message): void
    {
        $this->addRule($rule);
        $this->setRuleMessage($rule->getName(), $message);
    }

    private function getRuleSet(): array
    {
        return ['weight' => 'greater-or-equals:min_weight|less-or-equals:max_weight'];
    }

    private function clearErrors(): void
    {
        $this->errors = [];
    }
}
