<?php

declare(strict_types=1);

namespace OCA\BytarsSchool\AppInfo;

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

	/** @psalm-suppress PossiblyUnusedMethod */
	public function __construct() {
		parent::__construct(self::APP_ID);
	}

	public function register(IRegistrationContext $context): void {
		// Register the settings controller
		$context->registerController('OCA\BytarsSchool\Controller\SettingsController');
		
		// Register admin settings
		$context->registerSettings('OCA\BytarsSchool\Settings\Admin');
		
		// Register event listeners for user provisioning
		$context->registerEventListener(BeforeUserLoggedInEvent::class, UserProvisioning::class);
		$context->registerEventListener(UserLoggedInEvent::class, UserProvisioning::class);
	}

	public function boot(IBootContext $context): void {
		$serverContainer = $context->getServerContainer();
		
		// Get required services
		$config = $serverContainer->get(IConfig::class);
		$userManager = $serverContainer->get(IUserManager::class);
		$logger = $serverContainer->get(LoggerInterface::class);

		// Check if Directus integration is configured
		$directusUrl = $config->getAppValue(self::APP_ID, 'directus_url', '');
		
		if (!empty($directusUrl)) {
			// Register the Directus user backend
			$directusBackend = new DirectusUserBackend($config, $logger);
			$userManager->registerBackend($directusBackend);
			
			$logger->info('Directus user backend registered for BytarsSchool app');
		}
	}
}
