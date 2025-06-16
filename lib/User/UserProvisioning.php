<?php

declare(strict_types=1);

namespace OCA\BytarsSchool\User;

use Exception;
use OCA\BytarsSchool\AppInfo\Application;
use OCA\BytarsSchool\UserBackend\DirectusUserBackend;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserManager;
use OCP\User\Events\BeforeUserLoggedInEvent;
use OCP\User\Events\UserLoggedInEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use Psr\Log\LoggerInterface;

/**
 * @template-implements IEventListener<BeforeUserLoggedInEvent|UserLoggedInEvent>
 */
class UserProvisioning implements IEventListener {
	
	private IConfig $config;
	private IUserManager $userManager;
	private IGroupManager $groupManager;
	private LoggerInterface $logger;

	public function __construct(
		IConfig $config,
		IUserManager $userManager,
		IGroupManager $groupManager,
		LoggerInterface $logger
	) {
		$this->config = $config;
		$this->userManager = $userManager;
		$this->groupManager = $groupManager;
		$this->logger = $logger;
	}

	public function handle(Event $event): void {
		if ($event instanceof BeforeUserLoggedInEvent) {
			$this->handleBeforeLogin($event);
		} elseif ($event instanceof UserLoggedInEvent) {
			$this->handleAfterLogin($event);
		}
	}
	private function handleBeforeLogin(BeforeUserLoggedInEvent $event): void {
		$uid = $event->getUid();
		$autoProvision = $this->config->getAppValue(Application::APP_ID, 'auto_provision_users', 'false');
		
		if ($autoProvision === 'true' && !$this->userManager->userExists($uid)) {
			try {
				$this->createUserFromDirectus($uid);
			} catch (Exception $e) {
				$this->logger->error('Error auto-provisioning user: ' . $e->getMessage());
			}
		}
	}

	private function handleAfterLogin(UserLoggedInEvent $event): void {
		$user = $event->getUser();
		$defaultGroup = $this->config->getAppValue(Application::APP_ID, 'default_group', '');
		
		if (!empty($defaultGroup)) {
			$this->ensureUserInGroup($user, $defaultGroup);
		}
	}
	private function createUserFromDirectus(string $uid): void {
		$directusUrl = $this->config->getAppValue(Application::APP_ID, 'directus_url', '');
		$adminToken = $this->config->getAppValue(Application::APP_ID, 'directus_admin_token', '');

		if (empty($directusUrl) || empty($adminToken)) {
			throw new Exception('Directus configuration incomplete');
		}

		// Get user data from Directus
		$ch = curl_init();
		curl_setopt_array($ch, [
			CURLOPT_URL => rtrim($directusUrl, '/') . '/users?filter[email][_eq]=' . urlencode($uid),
			CURLOPT_HTTPHEADER => [
				'Authorization: Bearer ' . $adminToken,
				'Content-Type: application/json'
			],
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_TIMEOUT => 30,
			CURLOPT_SSL_VERIFYPEER => false
		]);

		$response = curl_exec($ch);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$error = curl_error($ch);
		curl_close($ch);

		if ($error) {
			throw new Exception('cURL error: ' . $error);
		}

		if ($httpCode === 200) {
			$data = json_decode($response, true);
			if (isset($data['data']) && count($data['data']) > 0) {
				$directusUser = $data['data'][0];
				
				// Create user in Nextcloud
				$user = $this->userManager->createUser($uid, ''); // No password needed as auth is handled by Directus
				
				if ($user) {
					// Set display name
					$displayName = trim(($directusUser['first_name'] ?? '') . ' ' . ($directusUser['last_name'] ?? ''));
					if (!empty($displayName)) {
						$user->setDisplayName($displayName);
					}

					// Set email
					if (!empty($directusUser['email'])) {
						$user->setEmailAddress($directusUser['email']);
					}

					$this->logger->info('Auto-provisioned user: ' . $uid);
				}
			}
		}
	}

	private function ensureUserInGroup(IUser $user, string $groupId): void {
		$group = $this->groupManager->get($groupId);
		
		if (!$group) {
			// Create group if it doesn't exist
			$group = $this->groupManager->createGroup($groupId);
		}
		
		if ($group && !$group->inGroup($user)) {
			$group->addUser($user);
			$this->logger->info('Added user ' . $user->getUID() . ' to group ' . $groupId);
		}
	}
}
