<?php

declare(strict_types=1);

namespace OCA\BytarsSchool\UserBackend;

use Exception;
use OCA\BytarsSchool\AppInfo\Application;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\IAppConfig;
use OCP\ILogger;
use OCP\User\Backend\ABackend;
use OCP\User\Backend\ICheckPasswordBackend;
use OCP\User\Backend\ICountUsersBackend;
use OCP\User\Backend\IGetDisplayNameBackend;
use OCP\User\Backend\IGetHomeBackend;
use OCP\User\Backend\IProvideEnabledStateBackend;
use OCP\UserInterface;
use Psr\Log\LoggerInterface;

class DirectusUserBackend extends ABackend implements
	ICheckPasswordBackend,
	ICountUsersBackend,
	IGetDisplayNameBackend,
	IGetHomeBackend,
	IProvideEnabledStateBackend {

	private IAppConfig $appConfig;
	private LoggerInterface $logger;
	private array $userCache = [];
	public function __construct(IAppConfig $appConfig, LoggerInterface $logger) {
		$this->appConfig = $appConfig;
		$this->logger = $logger;
	}
	/**
	 * Check if backend implements actions
	 */
	public function implementsActions(int $actions): bool {
		return (bool)(
			($actions & UserInterface::CHECK_PASSWORD) ||
			($actions & UserInterface::COUNT_USERS) ||
			($actions & UserInterface::GET_DISPLAYNAME) ||
			($actions & UserInterface::GET_HOME) ||
			($actions & UserInterface::PROVIDE_ENABLED)
		);
	}

	/**
	 * Delete a user
	 */
	public function deleteUser(string $uid): bool {
		return false; // We don't allow deleting users from Directus through Nextcloud
	}

	/**
	 * Get users
	 */
	public function getUsers(string $search = '', ?int $limit = null, ?int $offset = null): array {
		try {
			$directusUsers = $this->getDirectusUsers($search, $limit, $offset);
			$users = [];
			foreach ($directusUsers as $user) {
				$users[] = $user['email'];
			}
			return $users;
		} catch (Exception $e) {
			$this->logger->error('Error getting users from Directus: ' . $e->getMessage());
			return [];
		}
	}

	/**
	 * Check if user exists
	 */
	public function userExists(string $uid): bool {
		try {
			$user = $this->getDirectusUser($uid);
			return $user !== null;
		} catch (Exception $e) {
			$this->logger->error('Error checking if user exists in Directus: ' . $e->getMessage());
			return false;
		}
	}

	/**
	 * Get display name
	 */
	public function getDisplayName(string $uid): string {
		try {
			$user = $this->getDirectusUser($uid);
			if ($user) {
				return $user['first_name'] . ' ' . $user['last_name'];
			}
		} catch (Exception $e) {
			$this->logger->error('Error getting display name from Directus: ' . $e->getMessage());
		}
		return $uid;
	}

	/**
	 * Get display names
	 */
	public function getDisplayNames(string $search = '', ?int $limit = null, ?int $offset = null): array {
		try {
			$directusUsers = $this->getDirectusUsers($search, $limit, $offset);
			$displayNames = [];
			foreach ($directusUsers as $user) {
				$displayNames[$user['email']] = $user['first_name'] . ' ' . $user['last_name'];
			}
			return $displayNames;
		} catch (Exception $e) {
			$this->logger->error('Error getting display names from Directus: ' . $e->getMessage());
			return [];
		}
	}
	/**
	 * Check password - In Directus, we authenticate with email and password
	 */
	public function checkPassword(string $uid, string $password): string|bool {
		try {
			// In Directus, authentication is done with email, so $uid should be the email
			$authenticated = $this->authenticateWithDirectus($uid, $password);
			if ($authenticated) {
				// Return the email as the user ID
				return $uid;
			}
		} catch (Exception $e) {
			$this->logger->error('Error authenticating with Directus: ' . $e->getMessage());
		}
		return false;
	}

	/**
	 * Count users
	 */
	public function countUsers(): int {
		try {
			return $this->getDirectusUserCount();
		} catch (Exception $e) {
			$this->logger->error('Error counting users in Directus: ' . $e->getMessage());
			return 0;
		}
	}

	/**
	 * Get home directory
	 */
	public function getHome(string $uid): string|bool {
		return false; // Use default home directory
	}

	/**
	 * Check if user is enabled
	 */
	public function isUserEnabled(string $uid): bool {
		try {
			$user = $this->getDirectusUser($uid);
			return $user && ($user['status'] === 'active');
		} catch (Exception $e) {
			$this->logger->error('Error checking if user is enabled in Directus: ' . $e->getMessage());
			return false;
		}
	}	/**
	 * Authenticate with Directus API using email and password
	 */	private function authenticateWithDirectus(string $email, string $password): bool {
		$directusUrl = $this->appConfig->getValueString(Application::APP_ID, 'directus_url', '');
		
		if (empty($directusUrl)) {
			throw new Exception('Directus URL not configured');
		}
		
		// Validate email format
		if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
			$this->logger->debug('Invalid email format for authentication: ' . $email);
			return false;
		}

		$this->logger->debug('Attempting Directus authentication for email: ' . $email);

		$ch = curl_init();
		curl_setopt_array($ch, [
			CURLOPT_URL => rtrim($directusUrl, '/') . '/auth/login',
			CURLOPT_POST => true,
			CURLOPT_POSTFIELDS => json_encode([
				'email' => $email,
				'password' => $password
			]),
			CURLOPT_HTTPHEADER => [
				'Content-Type: application/json',
				'Accept: application/json'
			],
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_TIMEOUT => 30,
			CURLOPT_CONNECTTIMEOUT => 10,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_USERAGENT => 'Nextcloud Bytars School Integration'
		]);

		$response = curl_exec($ch);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$error = curl_error($ch);
		curl_close($ch);

		if ($error) {
			$this->logger->error('cURL error during Directus authentication: ' . $error);
			throw new Exception('cURL error: ' . $error);
		}

		$this->logger->debug('Directus authentication response code: ' . $httpCode);

		if ($httpCode === 200) {
			$data = json_decode($response, true);
			$success = isset($data['data']['access_token']);
			$this->logger->info('Directus authentication ' . ($success ? 'successful' : 'failed') . ' for email: ' . $email);
			return $success;
		} elseif ($httpCode === 401) {
			$this->logger->info('Directus authentication failed - invalid credentials for email: ' . $email);
			return false;
		} else {
			$this->logger->error('Directus authentication failed with HTTP code: ' . $httpCode . ' for email: ' . $email);
			return false;
		}
	}
	/**
	 * Get user from Directus
	 */
	private function getDirectusUser(string $email): ?array {		if (isset($this->userCache[$email])) {
			return $this->userCache[$email];
		}

		$directusUrl = $this->appConfig->getValueString(Application::APP_ID, 'directus_url', '');
		$adminToken = $this->appConfig->getValueString(Application::APP_ID, 'directus_admin_token', '');

		if (empty($directusUrl) || empty($adminToken)) {
			throw new Exception('Directus configuration incomplete');
		}

		$ch = curl_init();
		curl_setopt_array($ch, [
			CURLOPT_URL => rtrim($directusUrl, '/') . '/users?filter[email][_eq]=' . urlencode($email),
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
				$user = $data['data'][0];
				$this->userCache[$email] = $user;
				return $user;
			}
		}

		return null;
	}
	/**
	 * Get users from Directus
	 */	private function getDirectusUsers(string $search = '', ?int $limit = null, ?int $offset = null): array {
		$directusUrl = $this->appConfig->getValueString(Application::APP_ID, 'directus_url', '');
		$adminToken = $this->appConfig->getValueString(Application::APP_ID, 'directus_admin_token', '');

		if (empty($directusUrl) || empty($adminToken)) {
			throw new Exception('Directus configuration incomplete');
		}

		$url = rtrim($directusUrl, '/') . '/users';
		$params = [];

		if (!empty($search)) {
			$params['search'] = $search;
		}
		if ($limit !== null) {
			$params['limit'] = $limit;
		}
		if ($offset !== null) {
			$params['offset'] = $offset;
		}

		if (!empty($params)) {
			$url .= '?' . http_build_query($params);
		}

		$ch = curl_init();
		curl_setopt_array($ch, [
			CURLOPT_URL => $url,
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
			return $data['data'] ?? [];
		}

		return [];
	}
	/**
	 * Get user count from Directus
	 */	private function getDirectusUserCount(): int {
		$directusUrl = $this->appConfig->getValueString(Application::APP_ID, 'directus_url', '');
		$adminToken = $this->appConfig->getValueString(Application::APP_ID, 'directus_admin_token', '');

		if (empty($directusUrl) || empty($adminToken)) {
			throw new Exception('Directus configuration incomplete');
		}

		$ch = curl_init();
		curl_setopt_array($ch, [
			CURLOPT_URL => rtrim($directusUrl, '/') . '/users?aggregate[count]=*',
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
			return $data['data'][0]['count'] ?? 0;
		}

		return 0;
	}
}
