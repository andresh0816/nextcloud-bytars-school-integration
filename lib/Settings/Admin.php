<?php

declare(strict_types=1);

namespace OCA\BytarsSchool\Settings;

use OCA\BytarsSchool\AppInfo\Application;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IAppConfig;
use OCP\IURLGenerator;
use OCP\Settings\ISettings;
use OCP\Util;

class Admin implements ISettings {
	private IAppConfig $appConfig;
	private IURLGenerator $urlGenerator;

	public function __construct(IAppConfig $appConfig, IURLGenerator $urlGenerator) {
		$this->appConfig = $appConfig;
		$this->urlGenerator = $urlGenerator;
	}
	public function getForm(): TemplateResponse {
		// Load the settings script and styles
		Util::addScript(Application::APP_ID, 'settings');
		Util::addStyle(Application::APP_ID, 'settings');

		// Prepare data for the template
		$parameters = [
			'action_url' => $this->urlGenerator->linkToRoute(Application::APP_ID . '.settings.saveAdmin'),
			'test_url' => $this->urlGenerator->linkToRoute(Application::APP_ID . '.settings.testConnection'),
			'directus_url' => $this->appConfig->getValueString(Application::APP_ID, 'directus_url', ''),
			'directus_admin_token' => $this->appConfig->getValueString(Application::APP_ID, 'directus_admin_token', ''),
			'auto_provision_users' => $this->appConfig->getValueBool(Application::APP_ID, 'auto_provision_users', false),
			'default_group' => $this->appConfig->getValueString(Application::APP_ID, 'default_group', ''),
		];

		return new TemplateResponse(Application::APP_ID, 'admin', $parameters);
	}

	public function getSection(): string {
		return Application::APP_ID;
	}

	public function getPriority(): int {
		return 50;
	}
}
