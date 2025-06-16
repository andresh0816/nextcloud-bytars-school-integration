<?php

declare(strict_types=1);

namespace OCA\BytarsSchool\Settings;

use OCA\BytarsSchool\AppInfo\Application;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IConfig;
use OCP\Settings\ISettings;
use OCP\Util;

class Admin implements ISettings {
	private IConfig $config;
	public function __construct(IConfig $config) {
		$this->config = $config;
	}

	public function getForm(): TemplateResponse {
		// Ensure admin scripts and styles are enqueued
		Util::addScript(Application::APP_ID, 'admin');
		Util::addStyle(Application::APP_ID, 'admin');
		$parameters = [
			'directus_url' => $this->config->getAppValue(Application::APP_ID, 'directus_url', ''),
			'directus_admin_token' => $this->config->getAppValue(Application::APP_ID, 'directus_admin_token', ''),
			'auto_provision_users' => $this->config->getAppValue(Application::APP_ID, 'auto_provision_users', 'false'),
			'default_group' => $this->config->getAppValue(Application::APP_ID, 'default_group', ''),
		];

		return new TemplateResponse(Application::APP_ID, 'admin', $parameters);
	}

	public function getSection(): string {
		return 'security';
	}

	public function getPriority(): int {
		return 50;
	}
}
