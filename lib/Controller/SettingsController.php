<?php

namespace OCA\BytarsSchool\Controller;

use OCA\BytarsSchool\AppInfo\Application;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IConfig;
use OCP\IRequest;

class SettingsController extends Controller {
    private IConfig $config;

    public function __construct($appName, IRequest $request, IConfig $config) {
        parent::__construct($appName, $request);
        $this->config = $config;
    }    /**
     * Save admin settings
     */
    #[NoCSRFRequired]
    public function saveAdmin(): JSONResponse {
        // Parse JSON body or fallback to parameters
        $body = $this->request->getBody() ?? '';
        $data = json_decode($body, true);
        if (!is_array($data)) {
            $data = $this->request->getParams();
        }
        try {
            // Save all settings
            $this->config->setAppValue(Application::APP_ID, 'directus_url', $data['directus_url'] ?? '');
            $this->config->setAppValue(Application::APP_ID, 'directus_admin_token', $data['directus_admin_token'] ?? '');
            $this->config->setAppValue(Application::APP_ID, 'default_group', $data['default_group'] ?? '');
            $this->config->setAppValue(Application::APP_ID, 'auto_provision_users', !empty($data['auto_provision_users']) ? 'true' : 'false');

            return new JSONResponse(['success' => true, 'message' => 'Settings saved successfully']);
        } catch (\Exception $e) {
            return new JSONResponse(['success' => false, 'message' => 'Error saving settings: ' . $e->getMessage()]);
        }
    }

    /**
     * Test connection to Directus
     */
    #[NoCSRFRequired]
    public function testConnection(): JSONResponse {
        // Parse JSON body or fallback to parameters
        $body = $this->request->getBody() ?? '';
        $bodyData = json_decode($body, true);
        if (!is_array($bodyData)) {
            $bodyData = $this->request->getParams();
        }
        // Use POSTed values or fallback to saved config
        $directusUrl = $bodyData['directus_url'] ?? $this->config->getAppValue(Application::APP_ID, 'directus_url', '');
        $adminToken = $bodyData['directus_admin_token'] ?? $this->config->getAppValue(Application::APP_ID, 'directus_admin_token', '');

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
