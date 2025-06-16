<?php

declare(strict_types=1);

namespace OCA\BytarsSchool\AppInfo;

use OCA\BytarsSchool\Settings\Admin;
use OCA\BytarsSchool\User\UserProvisioning;
use OCA\BytarsSchool\UserBackend\DirectusUserBackend;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\IConfig;
use OCP\IUserManager;
use OCP\User\Events\BeforeUserLoggedInEvent;
use OCP\User\Events\UserLoggedInEvent;
use Psr\Log\LoggerInterface;

class Application extends App implements IBootstrap {
	public const APP_ID = 'bytarsschool';

	public function __construct() {
		parent::__construct(self::APP_ID);
	}

	public function register(IRegistrationContext $context): void {
		// Register admin settings
		$context->registerSettings(Admin::class);
		
		// Register event listeners for user provisioning
		$context->registerEventListener(BeforeUserLoggedInEvent::class, UserProvisioning::class);
		$context->registerEventListener(UserLoggedInEvent::class, UserProvisioning::class);
		
		// Register user backend in registration phase
		$container = $this->getContainer();
		$config = $container->get(IConfig::class);
		$directusUrl = $config->getAppValue(self::APP_ID, 'directus_url', '');
		
		if (!empty($directusUrl)) {
			$logger = $container->get(LoggerInterface::class);
			$userManager = $container->get(IUserManager::class);
			$directusBackend = new DirectusUserBackend($config, $logger);
			$userManager->registerBackend($directusBackend);
		}
	}

	public function boot(IBootContext $context): void {
		// Boot logic can be added here if needed
	}
}
