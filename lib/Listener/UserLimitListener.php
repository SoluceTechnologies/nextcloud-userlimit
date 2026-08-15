<?php

declare(strict_types=1);

namespace OCA\UserLimit\Listener;

use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\IAppConfig;
use OCP\IUserManager;
use OCP\HintException;
use OCP\User\Events\BeforeUserCreatedEvent;
use Psr\Log\LoggerInterface;

/**
 * @template-implements IEventListener<BeforeUserCreatedEvent>
 */
class UserLimitListener implements IEventListener {

    private const APP_ID = 'userlimit';
    private const CONFIG_KEY = 'limit';
    private const DEFAULT_LIMIT = 5;

    public function __construct(
        private IUserManager $userManager,
        private IAppConfig $appConfig,
        private LoggerInterface $logger,
    ) {
    }

    public function handle(Event $event): void {
        if (!($event instanceof BeforeUserCreatedEvent)) {
            return;
        }

        // NC 34's typed AppConfig throws AppConfigTypeConflictException when the
        // stored value's type doesn't match the read type. Degrade to the
        // default rather than fataling every user creation.
        try {
            $limit = $this->appConfig->getValueInt(
                self::APP_ID,
                self::CONFIG_KEY,
                self::DEFAULT_LIMIT
            );
        } catch (\Throwable $e) {
            $this->logger->error(
                'Failed to read userlimit config, falling back to default.',
                ['exception' => $e, 'default' => self::DEFAULT_LIMIT]
            );
            $limit = self::DEFAULT_LIMIT;
        }

        // 0 or negative disables the cap entirely.
        if ($limit <= 0) {
            $this->logger->debug('User limit disabled (limit <= 0), allowing.');
            return;
        }

        $current = array_sum($this->userManager->countUsers());
        $uid = $event->getUid();

        if ($current >= $limit) {
            $this->logger->warning(
                'Blocked user creation: instance cap reached.',
                ['uid' => $uid, 'current' => $current, 'limit' => $limit]
            );
            throw new HintException(
                'User limit reached',
                "This instance is capped at {$limit} users."
            );
        }

        $this->logger->info(
            'User creation permitted.',
            ['uid' => $uid, 'current' => $current, 'limit' => $limit]
        );
    }
}
