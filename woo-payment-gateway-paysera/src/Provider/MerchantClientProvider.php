<?php

declare(strict_types=1);

namespace Paysera\Provider;

use Exception;
use Paysera\DeliveryApi\MerchantClient\ClientFactory;
use Paysera\DeliveryApi\MerchantClient\MerchantClient;
use Paysera\Service\LoggerInterface;

class MerchantClientProvider implements MerchantClientProviderInterface
{
    private const BASE_URL = 'https://delivery-api.paysera.com/rest/v1/';

    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function getMerchantClient(?int $macId, ?string $macSecret): ?MerchantClient
    {
        if ($macId === null || $macSecret === null) {
            return null;
        }

        $merchantClient = null;
        $clientFactory = new ClientFactory([
            'base_url' => self::BASE_URL,
            'mac' => [
                'mac_id' => $macId,
                'mac_secret' => $macSecret,
            ],
        ]);

        try {
            $merchantClient = $clientFactory->getMerchantClient();
        } catch (Exception $exception) {
            $this->logger->error('Cannot create merchant client', $exception);
        }

        return $merchantClient;
    }
}
