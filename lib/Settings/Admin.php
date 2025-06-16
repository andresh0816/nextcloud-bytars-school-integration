<?php

declare(strict_types=1);

namespace OCA\BytarsSchool\Settings;

use OCP\AppFramework\Http\TemplateResponse;
use OCP\IConfig;
use OCP\Settings\ISettings;

class Admin implements ISettings {
	private IConfig $config;

	public function __construct(IConfig $config) {
		$this->config = $config;
	}

	public function getForm(): TemplateResponse {
		$parameters = [
			'directus_url' => $this->config->getAppValue('bytarsschool', 'directus_url', ''),
			'directus_admin_token' => $this->config->getAppValue('bytarsschool', 'directus_admin_token', ''),
			'auto_provision_users' => $this->config->getAppValue('bytarsschool', 'auto_provision_users', 'false'),
			'default_group' => $this->config->getAppValue('bytarsschool', 'default_group', ''),
		];

		return new TemplateResponse('bytarsschool', 'admin', $parameters);
	}

	public function getSection(): string {
		return 'security';
	}

	public function getPriority(): int {
		return 50;
	}
}
