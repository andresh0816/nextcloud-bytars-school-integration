<div id="bytarsschool-admin" class="section">
	<h2><?php p($l->t('Bytars School - Directus Integration')); ?></h2>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
	const form = document.getElementById('bytarsschool-admin-form');
	const saveButton = document.getElementById('bytarsschool-save-settings');
	const testButton = document.getElementById('bytarsschool-test-connection');
	const messageDiv = document.getElementById('bytarsschool-message');
	const statusDiv = document.getElementById('bytarsschool-connection-status');
	const detailsDiv = document.getElementById('connection-details');

	// Function to show messages
	function showMessage(message, type = 'info') {
		messageDiv.textContent = message;
		messageDiv.className = 'settings-message ' + type;
		messageDiv.classList.remove('hidden');
		
		// Auto-hide success messages after 5 seconds
		if (type === 'success') {
			setTimeout(() => {
				messageDiv.classList.add('hidden');
			}, 5000);
		}
	}

	// Function to show connection status
	function showConnectionStatus(data, type) {
		let html = '<div class="status-' + type + '">';
		html += '<h4>' + (type === 'success' ? '<?php p($l->t('Connection Successful')); ?>' : '<?php p($l->t('Connection Failed')); ?>') + '</h4>';
		html += '<p>' + data.message + '</p>';
		
		if (data.user && type === 'success') {
			html += '<p><strong><?php p($l->t('Connected as')); ?>:</strong> ' + data.user + '</p>';
		}
		html += '</div>';
		
		detailsDiv.innerHTML = html;
		statusDiv.classList.remove('hidden');
	}

	// Validate URL format
	function validateDirectusUrl(url) {
		try {
			const urlObj = new URL(url);
			return urlObj.protocol === 'http:' || urlObj.protocol === 'https:';
		} catch (e) {
			return false;
		}
	}

	// Handle form submission
	form.addEventListener('submit', function(e) {
		e.preventDefault();
		
		const directusUrl = document.getElementById('directus-url').value.trim();
		const adminToken = document.getElementById('directus-admin-token').value.trim();
		
		// Validate required fields
		if (!directusUrl) {
			showMessage('<?php p($l->t('Directus URL is required')); ?>', 'error');
			document.getElementById('directus-url').focus();
			return;
		}
		
		if (!validateDirectusUrl(directusUrl)) {
			showMessage('<?php p($l->t('Please enter a valid URL (including http:// or https://)')); ?>', 'error');
			document.getElementById('directus-url').focus();
			return;
		}
		
		if (!adminToken) {
			showMessage('<?php p($l->t('Admin token is required')); ?>', 'error');
			document.getElementById('directus-admin-token').focus();
			return;
		}

		// Prepare data
		const data = {
			directus_url: directusUrl.replace(/\/$/, ''), // Remove trailing slash
			directus_admin_token: adminToken,
			auto_provision_users: document.getElementById('auto-provision-users').checked ? 'true' : 'false',
			default_group: document.getElementById('default-group').value.trim()
		};

		// Save settings
		saveButton.disabled = true;
		saveButton.textContent = '<?php p($l->t('Saving...')); ?>';
		showMessage('<?php p($l->t('Saving configuration...')); ?>', 'info');

		fetch(OC.generateUrl('/apps/bytarsschool/settings'), {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'requesttoken': OC.requestToken
			},
			body: JSON.stringify(data)
		})
		.then(response => response.json())
		.then(result => {
			if (result.status === 'success') {
				showMessage('<?php p($l->t('Configuration saved successfully')); ?>', 'success');
				statusDiv.classList.add('hidden'); // Hide any previous connection status
			} else {
				showMessage(result.message || '<?php p($l->t('Error saving configuration')); ?>', 'error');
			}
		})
		.catch(error => {
			console.error('Save error:', error);
			showMessage('<?php p($l->t('Error saving configuration')); ?>', 'error');
		})
		.finally(() => {
			saveButton.disabled = false;
			saveButton.innerHTML = '<span class="icon icon-checkmark"></span><?php p($l->t('Save Configuration')); ?>';
		});
	});

	// Handle test connection
	testButton.addEventListener('click', function() {
		const directusUrl = document.getElementById('directus-url').value.trim();
		const adminToken = document.getElementById('directus-admin-token').value.trim();
		
		if (!directusUrl || !adminToken) {
			showMessage('<?php p($l->t('Please enter Directus URL and Admin Token before testing')); ?>', 'error');
			return;
		}
		
		if (!validateDirectusUrl(directusUrl)) {
			showMessage('<?php p($l->t('Please enter a valid URL')); ?>', 'error');
			return;
		}

		testButton.disabled = true;
		testButton.innerHTML = '<span class="icon icon-loading-small"></span><?php p($l->t('Testing...')); ?>';
		statusDiv.classList.add('hidden');

		fetch(OC.generateUrl('/apps/bytarsschool/test-connection'), {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'requesttoken': OC.requestToken
			},
			body: JSON.stringify({
				directus_url: directusUrl.replace(/\/$/, ''),
				directus_admin_token: adminToken
			})
		})
		.then(response => response.json())
		.then(data => {
			if (data.status === 'success') {
				showConnectionStatus(data, 'success');
				showMessage('<?php p($l->t('Connection test successful')); ?>', 'success');
			} else {
				showConnectionStatus(data, 'error');
				showMessage('<?php p($l->t('Connection test failed')); ?>', 'error');
			}
		})
		.catch(error => {
			console.error('Test error:', error);
			showMessage('<?php p($l->t('Connection test failed')); ?>', 'error');
			showConnectionStatus({message: '<?php p($l->t('Network error or invalid response')); ?>'}, 'error');
		})
		.finally(() => {
			testButton.disabled = false;
			testButton.innerHTML = '<span class="icon icon-play"></span><?php p($l->t('Test Connection')); ?>';
		});
	});

	// Real-time URL validation
	document.getElementById('directus-url').addEventListener('blur', function() {
		const url = this.value.trim();
		if (url && !validateDirectusUrl(url)) {
			showMessage('<?php p($l->t('Please enter a valid URL (e.g., https://your-directus.com)')); ?>', 'warning');
		}
	});
});
</script>

<style>
#bytarsschool-admin {
	max-width: 800px;
}

#bytarsschool-admin h2 {
	color: var(--color-main-text);
	margin-bottom: 10px;
}

#bytarsschool-admin .settings-hint {
	color: var(--color-text-maxcontrast);
	margin-bottom: 30px;
}

#bytarsschool-admin .personal-settings-setting-box {
	background: var(--color-main-background);
	border: 1px solid var(--color-border);
	border-radius: var(--border-radius-large);
	padding: 30px;
	margin-bottom: 20px;
}

#bytarsschool-admin .personal-settings-setting-box h3 {
	margin-top: 0;
	margin-bottom: 20px;
	color: var(--color-main-text);
	font-weight: 600;
}

#bytarsschool-admin .personal-settings-setting-box-form {
	margin-bottom: 20px;
}

#bytarsschool-admin .settings-label {
	display: block;
	font-weight: 600;
	margin-bottom: 8px;
	color: var(--color-main-text);
}

#bytarsschool-admin .required {
	color: var(--color-error);
}

#bytarsschool-admin input[type="url"],
#bytarsschool-admin input[type="password"],
#bytarsschool-admin input[type="text"] {
	width: 100%;
	max-width: 400px;
	padding: 10px 12px;
	border: 1px solid var(--color-border-dark);
	border-radius: var(--border-radius);
	background: var(--color-main-background);
	color: var(--color-main-text);
	font-size: 14px;
	box-sizing: border-box;
}

#bytarsschool-admin input[type="url"]:focus,
#bytarsschool-admin input[type="password"]:focus,
#bytarsschool-admin input[type="text"]:focus {
	outline: none;
	border-color: var(--color-primary);
	box-shadow: 0 0 0 2px var(--color-primary-light);
}

#bytarsschool-admin .checkbox-wrapper {
	display: flex;
	align-items: flex-start;
	gap: 10px;
}

#bytarsschool-admin .checkbox-label {
	font-weight: 600;
	color: var(--color-main-text);
	cursor: pointer;
	margin: 0;
}

#bytarsschool-admin .field-help {
	display: block;
	font-size: 13px;
	color: var(--color-text-maxcontrast);
	margin-top: 5px;
	line-height: 1.4;
}

#bytarsschool-admin .button-group {
	margin-top: 30px;
	display: flex;
	gap: 15px;
	align-items: center;
}

#bytarsschool-admin button {
	display: inline-flex;
	align-items: center;
	gap: 8px;
	padding: 10px 20px;
	border: none;
	border-radius: var(--border-radius);
	font-size: 14px;
	font-weight: 600;
	cursor: pointer;
	transition: all 0.2s ease;
}

#bytarsschool-admin button.primary {
	background: var(--color-primary);
	color: var(--color-primary-text);
}

#bytarsschool-admin button.primary:hover:not(:disabled) {
	background: var(--color-primary-hover);
}

#bytarsschool-admin button.secondary {
	background: var(--color-background-dark);
	color: var(--color-main-text);
	border: 1px solid var(--color-border-dark);
}

#bytarsschool-admin button.secondary:hover:not(:disabled) {
	background: var(--color-background-darker);
}

#bytarsschool-admin button:disabled {
	opacity: 0.6;
	cursor: not-allowed;
}

#bytarsschool-admin .settings-message {
	padding: 15px;
	border-radius: var(--border-radius);
	margin: 20px 0;
	font-weight: 500;
}

#bytarsschool-admin .settings-message.success {
	background: var(--color-success-background);
	color: var(--color-success-text);
	border: 1px solid var(--color-success);
}

#bytarsschool-admin .settings-message.error {
	background: var(--color-error-background);
	color: var(--color-error-text);
	border: 1px solid var(--color-error);
}

#bytarsschool-admin .settings-message.warning {
	background: var(--color-warning-background);
	color: var(--color-warning-text);
	border: 1px solid var(--color-warning);
}

#bytarsschool-admin .settings-message.info {
	background: var(--color-info-background);
	color: var(--color-info-text);
	border: 1px solid var(--color-info);
}

#bytarsschool-admin .connection-status {
	margin: 20px 0;
	padding: 0;
}

#bytarsschool-admin .status-success {
	background: var(--color-success-background);
	border: 1px solid var(--color-success);
	border-radius: var(--border-radius);
	padding: 15px;
}

#bytarsschool-admin .status-success h4 {
	color: var(--color-success-text);
	margin: 0 0 10px 0;
}

#bytarsschool-admin .status-success p {
	color: var(--color-success-text);
	margin: 5px 0;
}

#bytarsschool-admin .status-error {
	background: var(--color-error-background);
	border: 1px solid var(--color-error);
	border-radius: var(--border-radius);
	padding: 15px;
}

#bytarsschool-admin .status-error h4 {
	color: var(--color-error-text);
	margin: 0 0 10px 0;
}

#bytarsschool-admin .status-error p {
	color: var(--color-error-text);
	margin: 5px 0;
}

#bytarsschool-admin .hidden {
	display: none;
}

#bytarsschool-admin .icon {
	width: 16px;
	height: 16px;
	background-size: contain;
}
</style>
