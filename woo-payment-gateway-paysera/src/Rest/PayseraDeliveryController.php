<?php

declare(strict_types=1);

namespace Paysera\Rest;

use Exception;
use Paysera\Blocks\PayseraBlock;
use Paysera\Entity\PayseraDeliverySettings;
use Paysera\Helper\EventHandlingHelper;
use Paysera\Helper\PayseraDeliveryHelper;
use Paysera\Helper\SessionHelperInterface;
use Paysera\Provider\PayseraDeliverySettingsProvider;
use Paysera\Service\LoggerInterface;
use Paysera\Validation\PayseraDeliveryWeightValidator;
use Throwable;
use WP_REST_Server;

class PayseraDeliveryController extends PayseraRestController
{
    public const ORDER_EVENT_UPDATED = 'order_updated';

    public const CONTROLLER_BASE = 'delivery';

    protected $base = self::CONTROLLER_BASE;

    protected PayseraDeliveryHelper $payseraDeliveryHelper;
    protected SessionHelperInterface $sessionHelper;
    protected LoggerInterface $logger;
    protected EventHandlingHelper $eventHandlingHelper;

    public function __construct(
        PayseraDeliveryHelper $payseraDeliveryHelper,
        SessionHelperInterface $sessionHelper,
        LoggerInterface $logger,
        EventHandlingHelper $eventHandlingHelper
    ) {
        $this->payseraDeliveryHelper = $payseraDeliveryHelper;
        $this->sessionHelper = $sessionHelper;
        $this->logger = $logger;
        $this->eventHandlingHelper = $eventHandlingHelper;
    }

    /**
     * Register all routes related with stores
     */
    public function registerRoutes(): void
    {
        register_rest_route(
            $this->namespace,
            PayseraRestPaths::GET_TERMINAL_COUNTRIES,
            [
                [
                    'methods' => WP_REST_Server::CREATABLE,
                    'callback' => [$this, 'getTerminalCountries'],
                    'permission_callback' => '__return_true',
                    'args' => [
                        'shipping_method' => [
                            'required' => true,
                            'type' => 'string',
                        ],
                    ],
                ],
            ]
        );

        register_rest_route(
            $this->namespace,
            PayseraRestPaths::GET_TERMINAL_CITIES,
            [
                [
                    'methods' => WP_REST_Server::CREATABLE,
                    'callback' => [$this, 'getTerminalCities'],
                    'permission_callback' => '__return_true',
                    'args' => [
                        'shipping_method' => [
                            'required' => true,
                            'type' => 'string',
                        ],
                        'country' => [
                            'required' => true,
                            'type' => 'string',
                        ],
                    ],
                ],
            ]
        );

        register_rest_route(
            $this->namespace,
            PayseraRestPaths::GET_TERMINAL_LOCATION,
            [
                [
                    'methods' => WP_REST_Server::CREATABLE,
                    'callback' => [$this, 'getTerminalLocations'],
                    'permission_callback' => '__return_true',
                    'args' => [
                        'shipping_method' => [
                            'required' => true,
                            'type' => 'string',
                        ],
                        'country' => [
                            'required' => true,
                            'type' => 'string',
                        ],
                        'city' => [
                            'required' => true,
                            'type' => 'string',
                        ],
                    ],
                ],
            ]
        );

        register_rest_route(
            $this->namespace,
            PayseraRestPaths::SET_TERMINAL_LOCATION,
            [
                [
                    'methods' => WP_REST_Server::CREATABLE,
                    'callback' => [$this, 'setTerminalLocation'],
                    'permission_callback' => '__return_true',
                    'args' => [
                        'terminal' => [
                            'required' => true,
                            'type' => 'string',
                        ],
                    ],
                ],
            ]
        );

        register_rest_route(
            $this->namespace,
            PayseraRestPaths::GET_HOUSE_NO,
            [
                [
                    'methods' => WP_REST_Server::CREATABLE,
                    'callback' => [$this, 'getHouseNo'],
                    'permission_callback' => '__return_true',
                    'args' => [],
                ],
            ]
        );

        register_rest_route(
            $this->namespace,
            PayseraRestPaths::SET_HOUSE_NO,
            [
                [
                    'methods' => WP_REST_Server::CREATABLE,
                    'callback' => [$this, 'setHouseNo'],
                    'permission_callback' => '__return_true',
                    'args' => [
                        'field_name' => [
                            'required' => true,
                            'type' => 'string',
                        ],
                        'house_no' => [
                            'required' => true,
                            'type' => 'string',
                        ],
                    ],
                ],
            ]
        );

        register_rest_route(
            $this->namespace,
            PayseraRestPaths::CHECK_ORDER_UPDATES,
            [
                [
                    'methods' => WP_REST_Server::READABLE,
                    'callback' => [$this, 'checkDeliveryOrderUpdates'],
                    'permission_callback' => '__return_true',
                    'args' => [
                        'id' => [
                            'required' => true,
                            'type' => 'integer',
                        ],
                    ],
                ],
            ]
        );

        register_rest_route(
            $this->namespace,
            PayseraRestPaths::DELIVERY_VALIDATION,
            [
                [
                    'methods' => WP_REST_Server::READABLE,
                    'callback' => [$this, 'validateWeight'],
                    'permission_callback' => '__return_true',
                    'args' => [],
                ],
            ]
        );
    }

    /**
     * @return \WP_Error|\WP_HTTP_Response|\WP_REST_Response
     * @param mixed $request
     */
    public function getTerminalCountries($request)
    {
        $params = $request->get_params();
        $countries = [];

        if (!isset($params['shipping_method'])) {
            return rest_ensure_response($countries);
        }

        try {
            $countries = $this->payseraDeliveryHelper->getFormattedCountriesByShippingMethod(
                $params['shipping_method'],
            );
        } catch (Exception $exception) {
            $this->logger->error(sprintf('Error while getting terminal countries for %s', $params['shipping_method']), $exception);
        }

        return rest_ensure_response($countries);
    }

    /**
     * @return \WP_Error|\WP_HTTP_Response|\WP_REST_Response
     * @param mixed $request
     */
    public function getTerminalCities($request)
    {
        $params = $request->get_params();

        return rest_ensure_response(
            $this->payseraDeliveryHelper->getFormattedCitiesByCountry(
                $params['shipping_method'],
                $params['country'],
            )
        );
    }

    /**
     * @return \WP_Error|\WP_HTTP_Response|\WP_REST_Response
     * @param mixed $request
     */
    public function getTerminalLocations($request)
    {
        $params = $request->get_params();

        return rest_ensure_response(
            $this->payseraDeliveryHelper->getFormattedLocationsByCountryAndCity(
                $params['shipping_method'],
                $params['country'],
                $params['city'],
            )
        );
    }

    /**
     * @return \WP_Error|\WP_HTTP_Response|\WP_REST_Response
     * @param mixed $request
     */
    public function setTerminalLocation($request)
    {
        $params = $request->get_params();

        $this->sessionHelper->setData('paysera_terminal_location', $params['terminal']);
        $this->sessionHelper->setData('terminal', $params['terminal']);

        return rest_ensure_response(
            [
                'success' => true,
            ]
        );
    }

    /**
     * @return \WP_Error|\WP_HTTP_Response|\WP_REST_Response
     */
    public function getHouseNo()
    {
        return rest_ensure_response([
            PayseraBlock::PAYSERA_BILLING_HOUSE_NO => $this->sessionHelper->getData(PayseraDeliverySettings::BILLING_HOUSE_NO),
            PayseraBlock::PAYSERA_SHIPPING_HOUSE_NO => $this->sessionHelper->getData(PayseraDeliverySettings::SHIPPING_HOUSE_NO),
        ]);
    }

    /**
     * @return \WP_Error|\WP_HTTP_Response|\WP_REST_Response
     * @param mixed $request
     */
    public function setHouseNo($request)
    {
        $params = $request->get_params();

        foreach ($params as $key => $val) {
            $params[$key] = sanitize_text_field(wp_unslash($val));
        }

        if ($params['field_name'] === PayseraBlock::PAYSERA_BILLING_HOUSE_NO) {
            $this->sessionHelper->setData(PayseraDeliverySettings::BILLING_HOUSE_NO, $params['house_no']);
        }

        if ($params['field_name'] === PayseraBlock::PAYSERA_SHIPPING_HOUSE_NO) {
            $this->sessionHelper->setData(PayseraDeliverySettings::SHIPPING_HOUSE_NO, $params['house_no']);
        }

        return rest_ensure_response([
            'success' => true,
        ]);
    }

    /**
     * @return \WP_Error|\WP_HTTP_Response|\WP_REST_Response
     * @param mixed $request
     */
    public function checkDeliveryOrderUpdates($request)
    {
        $isSuccess = true;

        try {
            $params = $request->get_params();
            $this->eventHandlingHelper->handle(
                PayseraDeliverySettings::DELIVERY_ORDER_EVENT_UPDATED,
                ['orderId' => $params['id']]
            );
        } catch (Throwable $e) {
            $this->logger->error(
                sprintf(
                    '%s got error: %s on %s:%s, with trace: %s',
                    static::class,
                    $e->getMessage(),
                    $e->getFile(),
                    $e->getLine(),
                    $e->getTraceAsString(),
                )
            );

            $isSuccess = false;
        }

        return rest_ensure_response(
            [
                'success' => $isSuccess,
            ]
        );
    }

    public function validateWeight($request)
    {
        $result = (new PayseraDeliveryWeightValidator(
            $this->sessionHelper,
            $this->payseraDeliveryHelper,
            new PayseraDeliverySettingsProvider
        ))
            ->validateWeight()
        ;

        return rest_ensure_response($result);
    }
}
