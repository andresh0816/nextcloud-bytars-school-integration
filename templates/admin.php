<div id="bytarsschool-admin" class="section">
	<h2><?php p($l->t('Bytars School - Directus Integration')); ?></h2>
	<p class="settings-hint"><?php p($l->t('Configure the connection to your Directus instance for SSO authentication')); ?></p>

	<div class="personal-settings-setting-box">
		<h3><?php p($l->t('Directus Configuration')); ?></h3>
		
		<div class="personal-settings-setting-box-form">
			<input type="url" 
				   id="directus-url" 
				   placeholder="<?php p($l->t('https://your-directus-instance.com')); ?>"
				   value="<?php p($_['directus_url']); ?>" />
			<label for="directus-url"><?php p($l->t('Directus URL')); ?></label>
		</div>

		<div class="personal-settings-setting-box-form">
			<input type="password" 
				   id="directus-admin-token" 
				   placeholder="<?php p($l->t('Admin Token from Directus')); ?>"
				   value="<?php p($_['directus_admin_token']); ?>" />
			<label for="directus-admin-token"><?php p($l->t('Admin Token')); ?></label>
			<em><?php p($l->t('Required to fetch user information from Directus')); ?></em>
		</div>

		<div class="personal-settings-setting-box-form">
			<input type="text" 
				   id="default-group" 
				   placeholder="<?php p($l->t('users')); ?>"
				   value="<?php p($_['default_group']); ?>" />
			<label for="default-group"><?php p($l->t('Default Group')); ?></label>
			<em><?php p($l->t('Default group for new users (optional)')); ?></em>
		</div>

		<div class="personal-settings-setting-box-form">
			<input type="checkbox" 
				   id="auto-provision-users" 
				   class="checkbox"
				   <?php if ($_['auto_provision_users'] === 'true') p('checked'); ?> />
			<label for="auto-provision-users"><?php p($l->t('Auto-provision users')); ?></label>
			<em><?php p($l->t('Automatically create users in Nextcloud when they login with Directus')); ?></em>
		</div>

		<div class="personal-settings-setting-box-form">
			<button id="bytarsschool-test-connection"><?php p($l->t('Test Connection')); ?></button>
			<button id="bytarsschool-save-settings"><?php p($l->t('Save Settings')); ?></button>
		</div>

		<div id="bytarsschool-message" class="hidden"></div>
	</div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
	const saveButton = document.getElementById('bytarsschool-save-settings');
	const testButton = document.getElementById('bytarsschool-test-connection');
	const messageDiv = document.getElementById('bytarsschool-message');

	function showMessage(message, type) {
		messageDiv.textContent = message;
		messageDiv.className = type;
		messageDiv.classList.remove('hidden');
		setTimeout(() => {
			messageDiv.classList.add('hidden');
		}, 5000);
	}

	saveButton.addEventListener('click', function() {
		const data = {
			directus_url: document.getElementById('directus-url').value,
			directus_admin_token: document.getElementById('directus-admin-token').value,
			auto_provision_users: document.getElementById('auto-provision-users').checked ? 'true' : 'false',
			default_group: document.getElementById('default-group').value
		};

		fetch(OC.generateUrl('/apps/bytarsschool/settings'), {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'requesttoken': OC.requestToken
			},
			body: JSON.stringify(data)
		})
		.then(response => response.json())
		.then(data => {
			if (data.status === 'success') {
				showMessage('<?php p($l->t('Settings saved successfully')); ?>', 'success');
			} else {
				showMessage('<?php p($l->t('Error saving settings')); ?>', 'error');
			}
		})
		.catch(error => {
			showMessage('<?php p($l->t('Error saving settings')); ?>', 'error');
		});
	});

	testButton.addEventListener('click', function() {
		testButton.disabled = true;
		testButton.textContent = '<?php p($l->t('Testing...')); ?>';

		fetch(OC.generateUrl('/apps/bytarsschool/test-connection'), {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'requesttoken': OC.requestToken
			}
		})
		.then(response => response.json())
		.then(data => {
			if (data.status === 'success') {
				showMessage(data.message + (data.user ? ' (' + data.user + ')' : ''), 'success');
			} else {
				showMessage(data.message, 'error');
			}
		})
		.catch(error => {
			showMessage('<?php p($l->t('Connection test failed')); ?>', 'error');
		})
		.finally(() => {
			testButton.disabled = false;
			testButton.textContent = '<?php p($l->t('Test Connection')); ?>';
		});
	});
});
</script>

<style>
#bytarsschool-admin .personal-settings-setting-box-form {
	margin-bottom: 15px;
}

#bytarsschool-admin input[type="url"],
#bytarsschool-admin input[type="password"],
#bytarsschool-admin input[type="text"] {
	width: 300px;
	margin-bottom: 5px;
}

#bytarsschool-admin label {
	display: block;
	font-weight: bold;
	margin-bottom: 5px;
}

#bytarsschool-admin em {
	display: block;
	font-size: 0.9em;
	color: #666;
	margin-top: 5px;
}

#bytarsschool-admin button {
	margin-right: 10px;
	padding: 8px 16px;
}

#bytarsschool-message {
	margin-top: 15px;
	padding: 10px;
	border-radius: 3px;
}

#bytarsschool-message.success {
	background-color: #d4edda;
	color: #155724;
	border: 1px solid #c3e6cb;
}

#bytarsschool-message.error {
	background-color: #f8d7da;
	color: #721c24;
	border: 1px solid #f5c6cb;
}

#bytarsschool-message.hidden {
	display: none;
}
</style>
