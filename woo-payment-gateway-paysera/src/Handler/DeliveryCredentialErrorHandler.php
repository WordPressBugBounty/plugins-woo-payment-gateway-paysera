<?php

declare(strict_types=1);

namespace Paysera\Handler;

defined('ABSPATH') || exit;

use Paysera\Entity\PayseraPaths;
use Paysera\Exception\RedirectStopException;
use Paysera\Factory\TransientNoticeFactory;
use Paysera\Provider\DeliveryNoticeMessageProvider;
use Paysera\Scoped\Paysera\DeliverySdk\Exception\CredentialsValidationException;
use Paysera\Scoped\Paysera\DeliverySdk\Exception\MerchantClientNotFoundException;
use Paysera\Scoped\Paysera\DeliverySdk\Exception\RateLimitExceededException;
use Paysera\Scoped\Paysera\DeliverySdk\Service\DeliveryLoggerInterface;
use Paysera\Service\TransientNoticeMessenger;
use Paysera\Service\RedirectorInterface;
use Throwable;

class DeliveryCredentialErrorHandler
{
    private const CONTEXT = 'delivery_credentials';
    private const TTL = 300;
    private const REDIRECT_URL = 'admin.php?page=paysera-delivery';

    private DeliveryNoticeMessageProvider $messageProvider;
    private TransientNoticeFactory $noticeFactory;
    private TransientNoticeMessenger $noticeMessenger;
    private DeliveryLoggerInterface $logger;
    private RedirectorInterface $redirector;

    public function __construct(
        DeliveryNoticeMessageProvider $messageProvider,
        TransientNoticeFactory $noticeFactory,
        TransientNoticeMessenger $noticeMessenger,
        DeliveryLoggerInterface $logger,
        RedirectorInterface $redirector
    ) {
        $this->messageProvider = $messageProvider;
        $this->noticeFactory = $noticeFactory;
        $this->noticeMessenger = $noticeMessenger;
        $this->logger = $logger;
        $this->redirector = $redirector;
    }

    /**
     * @throws RedirectStopException
     */
    public function handleEmptyCredentials(): void
    {
        $notice = $this->noticeFactory
            ->error($this->messageProvider->getEmptyCredentialsMessage())
            ->withContext(self::CONTEXT)
            ->build()
        ;

        $this->noticeMessenger->set($notice, null, self::TTL);
        $this->redirectToSettings();
    }

    /**
     * @throws RedirectStopException
     */
    public function handleInvalidCredentials(string $projectId): void
    {
        $this->sendErrorNotice(
            PayseraPaths::PAYSERA_MESSAGE . ' ' . wp_strip_all_tags($this->messageProvider->getInvalidCredentialsMessage()),
            $this->messageProvider->getInvalidCredentialsMessage()
        );
        $this->redirectToSettings();
    }

    /**
     * @throws RedirectStopException
     */
    public function handleValidationException(CredentialsValidationException $exception): void
    {
        $this->sendErrorNotice(
            PayseraPaths::PAYSERA_MESSAGE . ' ' . wp_strip_all_tags($this->messageProvider->getValidationFailedMessage($exception->getMessage())),
            $this->messageProvider->getValidationFailedMessage($exception->getMessage())
        );
        $this->redirectToSettings();
    }

    /**
     * @throws RedirectStopException
     */
    public function handleMerchantNotFound(MerchantClientNotFoundException $exception): void
    {
        $this->sendErrorNotice(
            PayseraPaths::PAYSERA_MESSAGE . ' ' . $this->messageProvider->getMerchantNotFoundMessage() . ' ' . $exception->getMessage(),
            $this->messageProvider->getMerchantNotFoundMessage()
        );
        $this->redirectToSettings();
    }

    /**
     * @throws RedirectStopException
     */
    public function handleRateLimitExceeded(RateLimitExceededException $exception): void
    {
        $this->sendWarningNotice(
            PayseraPaths::PAYSERA_MESSAGE . ' ' . $this->messageProvider->getRateLimitExceededMessage() . ' ' . $exception->getMessage(),
            $this->messageProvider->getRateLimitExceededMessage()
        );
        $this->redirectToSettings();
    }

    /**
     * @throws RedirectStopException
     */
    public function handleUnexpectedError(Throwable $exception): void
    {
        $this->sendErrorNotice(
            PayseraPaths::PAYSERA_MESSAGE . ' ' . $this->messageProvider->getValidationErrorMessage() . ' ' . $exception->getMessage(),
            $this->messageProvider->getValidationErrorMessage()
        );
        $this->redirectToSettings();
    }

    /**
     * @throws RedirectStopException
     */
    public function handleEnableWithoutCredentials(): void
    {
        $notice = $this->noticeFactory
            ->error($this->messageProvider->getEnableDeliveryWithoutCredentialsMessage())
            ->withContext('delivery_settings')
            ->build()
        ;

        $this->noticeMessenger->set($notice);
        $this->redirectToSettings();
    }

    private function sendErrorNotice(
        string $logMessage,
        string $noticeMessage
    ): void {
        $this->logger->error($logMessage);

        $notice = $this->noticeFactory
            ->error($noticeMessage)
            ->withContext(self::CONTEXT)
            ->build()
        ;

        $this->noticeMessenger->set($notice, null, self::TTL);
    }

    private function sendWarningNotice(
        string $logMessage,
        string $noticeMessage
    ): void {
        $this->logger->error($logMessage);

        $notice = $this->noticeFactory
            ->warning($noticeMessage)
            ->withContext(self::CONTEXT)
            ->build()
        ;

        $this->noticeMessenger->set($notice, null, self::TTL);
    }

    /**
     * @throws RedirectStopException
     */
    private function redirectToSettings(): void
    {
        $this->redirector->redirect(self::REDIRECT_URL);
    }
}
