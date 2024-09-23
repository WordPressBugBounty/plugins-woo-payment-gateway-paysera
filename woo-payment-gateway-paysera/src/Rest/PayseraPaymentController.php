<?php

declare(strict_types=1);

namespace Paysera\Rest;

use Paysera\Front\PayseraPaymentFrontHtml;
use Paysera\Generator\PayseraPaymentFieldGenerator;
use Paysera\Helper\PayseraPaymentLibraryHelper;
use Paysera\Provider\PayseraPaymentSettingsProvider;
use Paysera\Service\LoggerInterface;
use WP_REST_Server;

class PayseraPaymentController extends PayseraRestController
{
    protected $base = 'payment';

    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Register all routes related with stores
     */
    public function registerRoutes(): void
    {
        register_rest_route(
            $this->namespace,
            '/' . $this->base . '/countries',
            [
                [
                    'methods' => WP_REST_Server::READABLE,
                    'callback' => [$this, 'getCountries'],
                    'permission_callback' => '__return_true',
                ],
            ]
        );
    }

    /**
     * @return WP_REST_Response|WP_Error
     * @param mixed $request
     */
    public function getCountries($request)
    {
        $payseraPaymentSettings = (new PayseraPaymentSettingsProvider())->getPayseraPaymentSettings();
        $payseraPaymentFieldGenerator = new PayseraPaymentFieldGenerator(
            $payseraPaymentSettings,
            new PayseraPaymentFrontHtml(),
            new PayseraPaymentLibraryHelper($this->logger)
        );

        $countries = $payseraPaymentFieldGenerator->getPaymentBlockCountries();

        return rest_ensure_response($countries);
    }
}
