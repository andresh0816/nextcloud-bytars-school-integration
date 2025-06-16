<?php

namespace OCA\BytarsSchool\Controller;

use OCA\BytarsSchool\AppInfo\Application;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IAppConfig;
use OCP\IRequest;

class SettingsController extends Controller {
    private IAppConfig $appConfig;

    public function __construct($appName, IRequest $request, IAppConfig $appConfig) {
        parent::__construct($appName, $request);
        $this->appConfig = $appConfig;
    }

    /**
     * Save admin settings
     */
    public function saveAdmin($directus_url = '', $directus_admin_token = '', $auto_provision_users = false, $default_group = ''): JSONResponse {
        try {
            // Validate required fields
            if (empty($directus_url)) {
                return new JSONResponse([
                    'success' => false,
                    'message' => 'Directus URL is required'
                ]);
            }
            
            if (empty($directus_admin_token)) {
                return new JSONResponse([
                    'success' => false,
                    'message' => 'Admin token is required'
                ]);
            }

            // Save configuration
            $this->appConfig->setValueString(Application::APP_ID, 'directus_url', trim($directus_url, '/'));
            $this->appConfig->setValueString(Application::APP_ID, 'directus_admin_token', $directus_admin_token);
            $this->appConfig->setValueBool(Application::APP_ID, 'auto_provision_users', (bool)$auto_provision_users);
            $this->appConfig->setValueString(Application::APP_ID, 'default_group', $default_group);

            return new JSONResponse(['success' => true]);
        } catch (\Exception $e) {
            return new JSONResponse([
                'success' => false,
                'message' => 'Error saving settings: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Test connection to Directus
     */
    public function testConnection(): JSONResponse {
        $directusUrl = $this->appConfig->getValueString(Application::APP_ID, 'directus_url', '');
        $adminToken = $this->appConfig->getValueString(Application::APP_ID, 'directus_admin_token', '');

        if (empty($directusUrl) || empty($adminToken)) {
            return new JSONResponse([
                'success' => false,
                'message' => 'Please configure Directus settings first'
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
                CURLOPT_TIMEOUT => 15,
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
                throw new \Exception('cURL connection error: ' . $error);
            }

            if ($httpCode === 200) {
                $data = json_decode($response, true);
                return new JSONResponse([
                    'success' => true,
                    'message' => 'Connection successful',
                    'user' => $data['data']['email'] ?? 'Admin user'
                ]);
            } elseif ($httpCode === 401) {
                return new JSONResponse([
                    'success' => false,
                    'message' => 'Invalid or expired admin token'
                ]);
            } elseif ($httpCode === 404) {
                return new JSONResponse([
                    'success' => false,
                    'message' => 'Endpoint not found. Check Directus URL'
                ]);
            } else {
                return new JSONResponse([
                    'success' => false,
                    'message' => 'Directus server error. HTTP code: ' . $httpCode
                ]);
            }
        } catch (\Exception $e) {
            return new JSONResponse([
                'success' => false,
                'message' => 'Connection error: ' . $e->getMessage()
            ]);
        }
    }
}
