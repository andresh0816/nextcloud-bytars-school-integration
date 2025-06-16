<?php

declare(strict_types=1);

namespace OCA\BytarsSchool\Controller;

use OCA\BytarsSchool\AppInfo\Application;
use OCA\BytarsSchool\Settings\Admin;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\AuthorizedAdminSetting;
use OCP\AppFramework\Http\DataResponse;
use OCP\IConfig;
use OCP\IRequest;

class SettingsController extends Controller {
	private IConfig $config;

	public function __construct(IRequest $request, IConfig $config) {
		parent::__construct(Application::APP_ID, $request);
		$this->config = $config;
	}

	#[AuthorizedAdminSetting(settings: Admin::class)]
	public function saveSettings(
		string $directus_url = '',
		string $directus_admin_token = '',
		string $auto_provision_users = 'false',
		string $default_group = ''
	): DataResponse {
		$this->config->setAppValue(Application::APP_ID, 'directus_url', $directus_url);
		$this->config->setAppValue(Application::APP_ID, 'directus_admin_token', $directus_admin_token);
		$this->config->setAppValue(Application::APP_ID, 'auto_provision_users', $auto_provision_users);
		$this->config->setAppValue(Application::APP_ID, 'default_group', $default_group);

		return new DataResponse(['status' => 'success']);
	}

	#[AuthorizedAdminSetting(settings: Admin::class)]
	public function testConnection(): DataResponse {
		$directusUrl = $this->config->getAppValue(Application::APP_ID, 'directus_url', '');
		$adminToken = $this->config->getAppValue(Application::APP_ID, 'directus_admin_token', '');

		if (empty($directusUrl) || empty($adminToken)) {
			return new DataResponse([
				'status' => 'error',
				'message' => 'URL de Directus y token de administrador son requeridos'
			]);
		}

		try {
			$ch = curl_init();
			curl_setopt_array($ch, [
				CURLOPT_URL => rtrim($directusUrl, '/') . '/users/me',
				CURLOPT_HTTPHEADER => [
					'Authorization: Bearer ' . $adminToken,
					'Content-Type: application/json'
				],
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_TIMEOUT => 10,
				CURLOPT_SSL_VERIFYPEER => false
			]);

			$response = curl_exec($ch);
			$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			$error = curl_error($ch);
			curl_close($ch);

			if ($error) {
				throw new \Exception('Error de cURL: ' . $error);
			}

			if ($httpCode === 200) {
				$data = json_decode($response, true);
				return new DataResponse([
					'status' => 'success',
					'message' => 'Conexión exitosa con Directus',
					'user' => $data['data']['email'] ?? 'Usuario desconocido'
				]);
			} else {
				return new DataResponse([
					'status' => 'error',
					'message' => 'Error de autenticación. Código HTTP: ' . $httpCode
				]);
			}
		} catch (\Exception $e) {
			return new DataResponse([
				'status' => 'error',
				'message' => 'Error de conexión: ' . $e->getMessage()
			]);
		}
	}
}
