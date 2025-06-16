<?php

declare(strict_types=1);

use OCP\Util;

Util::addScript(OCA\BytarsSchool\AppInfo\Application::APP_ID, OCA\BytarsSchool\AppInfo\Application::APP_ID . '-main');
Util::addStyle(OCA\BytarsSchool\AppInfo\Application::APP_ID, OCA\BytarsSchool\AppInfo\Application::APP_ID . '-main');

?>

<div id="bytarsschool"></div>
