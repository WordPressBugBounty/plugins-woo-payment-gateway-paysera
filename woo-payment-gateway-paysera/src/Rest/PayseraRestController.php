<?php

declare(strict_types=1);

namespace Paysera\Rest;

use Paysera\Entity\PayseraPaths;
use WP_REST_Controller;

abstract class PayseraRestController extends WP_REST_Controller
{
    /**
     * Route base.
     *
     * @var string
     */
    protected $namespace = PayseraPaths::PAYSERA_REST_BASE;

    /**
     * Register all routes related with stores
     */
    abstract public function registerRoutes();
}
