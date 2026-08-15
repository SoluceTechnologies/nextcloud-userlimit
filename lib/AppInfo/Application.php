<?php

declare(strict_types=1);

namespace OCA\UserLimit\AppInfo;

use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\User\Events\BeforeUserCreatedEvent;
use OCA\UserLimit\Listener\UserLimitListener;

class Application extends App implements IBootstrap {

    public const APP_ID = 'userlimit';

    public function __construct() {
        parent::__construct(self::APP_ID);
    }

    public function register(IRegistrationContext $context): void {
        $context->registerEventListener(
            BeforeUserCreatedEvent::class,
            UserLimitListener::class
        );
    }

    public function boot(IBootContext $context): void {
    }
}
