<?php
script('bytarsschool', 'admin');
style('bytarsschool', 'admin');
?>

<div id="bytarsschool-admin" class="section">
	<h2 class="inlineblock"><?php p($l->t('Bytars School - Directus Integration')); ?></h2>
	<p class="settings-hint"><?php p($l->t('Configure Directus as identity provider for Nextcloud authentication')); ?></p>

	<div class="personal-settings-setting-box">
		<h3><?php p($l->t('Directus Server Configuration')); ?></h3>
		
		<form id="bytarsschool-admin-form">
			<div class="personal-settings-setting-box-form">
				<label for="directus-url" class="settings-label"><?php p($l->t('Directus URL')); ?> <span class="required">*</span></label>
				<input type="url" 
					   id="directus-url" 
					   name="directus_url"
					   placeholder="<?php p($l->t('https://your-directus-instance.com')); ?>"
					   value="<?php p($_['directus_url']); ?>" 
					   required />
				<em class="field-help"><?php p($l->t('Complete URL of your Directus instance including protocol (https://)')); ?></em>
			</div>
			<div class="personal-settings-setting-box-form">
				<label for="directus-admin-token" class="settings-label"><?php p($l->t('Admin Token')); ?> <span class="required">*</span></label>
				<input type="password" 
					   id="directus-admin-token" 
					   name="directus_admin_token"
					   placeholder="<?php p($l->t('Admin Token from Directus')); ?>"
					   value="<?php p($_['directus_admin_token']); ?>" 
					   required />
				<em class="field-help"><?php p($l->t('Admin token from Directus with user management permissions')); ?></em>
			</div>

			<div class="personal-settings-setting-box-form">
				<label for="default-group" class="settings-label"><?php p($l->t('Default Group')); ?></label>
				<input type="text" 
					   id="default-group" 
					   name="default_group"
					   placeholder="<?php p($l->t('users')); ?>"
					   value="<?php p($_['default_group']); ?>" />
				<em class="field-help"><?php p($l->t('Default group for new users (leave empty for no group assignment)')); ?></em>
			</div>

			<div class="personal-settings-setting-box-form checkbox-wrapper">
				<input type="checkbox" 
					   id="auto-provision-users" 
					   name="auto_provision_users"
					   class="checkbox"
					   <?php if ($_['auto_provision_users'] === 'true') p('checked'); ?> />
				<label for="auto-provision-users" class="checkbox-label"><?php p($l->t('Auto-provision users')); ?></label>
				<em class="field-help"><?php p($l->t('Automatically create Nextcloud users when they authenticate with Directus')); ?></em>
			</div>

			<div class="personal-settings-setting-box-form button-group">
				<button type="button" id="bytarsschool-test-connection" class="button secondary">
					<span class="icon icon-play"></span>
					<?php p($l->t('Test Connection')); ?>
				</button>
				<button type="submit" id="bytarsschool-save-settings" class="button primary">
					<span class="icon icon-checkmark"></span>
					<?php p($l->t('Save Configuration')); ?>
				</button>
			</div>
		</form>

		<div id="bytarsschool-message" class="settings-message hidden"></div>
		<div id="bytarsschool-connection-status" class="connection-status hidden">
			<div id="connection-details"></div>
		</div>
	</div>
</div>
