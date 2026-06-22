<?php
require_once __DIR__ . '/../api/config.php';

class ExternalApiService
{

    // In Hexagonal Architecture, this is an Adapter for an outgoing Port (External Service).

    private $apiKey;
    private $userId;
    private $baseUrl;

    public function __construct()
    {
        $this->apiKey = defined('EXTERNAL_API_KEY') ? EXTERNAL_API_KEY : '';
        $this->userId = defined('EXTERNAL_USER_ID') ? EXTERNAL_USER_ID : '';
        $this->baseUrl = defined('EXTERNAL_API_BASE') ? EXTERNAL_API_BASE : '';
    }

    /**
     * Fetch the list of plans from the external provider.
     */
    public function getPlans()
    {
        $url = defined('EXTERNAL_API_URL') ? EXTERNAL_API_URL : $this->baseUrl . '/plans/list_plan';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            throw new Exception("cURL Error: " . curl_error($ch));
        }
        curl_close($ch);

        // Return raw data or decoded, depending on architecture preference.
        return json_decode($response, true);
    }

    /**
     * Create an order on the external provider.
     * @param array $postData - Array of order data
     * @return array
     */
    public function createOrder($postData)
    {
        // Merge with defaults if needed
        $defaults = [
            'user_id' => $this->userId,
            'type_payment' => 'balance',
            'auto_renew' => 1
        ];

        $postData = array_merge($defaults, $postData);

        $url = $this->baseUrl . '/orders/create_order';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded',
            'x-api-key: ' . $this->apiKey
        ]);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            throw new Exception("External API Error: " . curl_error($ch));
        }
        curl_close($ch);

        return json_decode($response, true);
    }

    /**
     * Perform an action on a specific server (start, stop, restart).
     * @param string $externalId
     * @param string $action
     * @return array
     */
    public function serverAction($externalId, $action)
    {
        $url = $this->baseUrl . '/services/servers_actions?server_id=' . $externalId . '&force=true&action=' . $action;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded',
            'Content-Length: 0',
            'x-api-key: ' . $this->apiKey
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            throw new Exception("External API Error: " . curl_error($ch));
        }
        curl_close($ch);

        $jsonResponse = json_decode($response, true);

        return [
            'http_code' => $httpCode,
            'response' => $jsonResponse,
            'raw_response' => $response
        ];
    }

    /**
     * Get VNC credentials for a server.
     * @param string $externalId
     * @return array
     */
    public function getVnc($externalId)
    {
        $url = $this->baseUrl . '/services/vnc_server?server_id=' . $externalId;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded',
            'Content-Length: 0',
            'x-api-key: ' . $this->apiKey
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            throw new Exception("External API Error: " . curl_error($ch));
        }
        curl_close($ch);

        $jsonResponse = json_decode($response, true);

        return [
            'http_code' => $httpCode,
            'response' => $jsonResponse,
            'raw_response' => $response
        ];
    }
    /**
     * Reset the password for a server.
     * @param string $externalId
     * @return array
     */
    public function resetPassword($externalId)
    {
        $url = $this->baseUrl . '/services/reset_password?server_id=' . $externalId;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded',
            'Content-Length: 0',
            'x-api-key: ' . $this->apiKey
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            throw new Exception("External API Error: " . curl_error($ch));
        }
        curl_close($ch);

        $jsonResponse = json_decode($response, true);

        return [
            'http_code' => $httpCode,
            'response' => $jsonResponse,
            'raw_response' => $response
        ];
    }

    /**
     * Get detailed information for a specific server.
     * @param string $externalId - The server's external ID
     * @return array
     */
    public function getOneServer($externalId)
    {
        $url = $this->baseUrl . '/services/detail_server?id=' . $externalId;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'x-api-key: ' . $this->apiKey
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            throw new Exception("External API Error: " . curl_error($ch));
        }
        curl_close($ch);

        $jsonResponse = json_decode($response, true);

        return [
            'http_code' => $httpCode,
            'response' => $jsonResponse,
            'raw_response' => $response
        ];
    }

    /**
     * Get usage statistics for a specific server.
     * @param string $externalId - The server's external ID
     * @param string $specification - Type of stats: 'network', 'cpu', or 'memory'
     * @return array
     */
    public function getServerUsage($externalId, $specification)
    {
        $url = $this->baseUrl . '/services/usage_server?server_id=' . $externalId . '&specification=' . $specification;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'x-api-key: ' . $this->apiKey
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            throw new Exception("External API Error: " . curl_error($ch));
        }
        curl_close($ch);

        $jsonResponse = json_decode($response, true);

        return [
            'http_code' => $httpCode,
            'response' => $jsonResponse,
            'raw_response' => $response
        ];
    }
    /**
     * Get disk usage details for a specific server.
     * @param string $externalId - The server's external ID
     * @return array
     */
    public function getDetailDisk($externalId)
    {
        $url = $this->baseUrl . '/services/detail_disk?server_id=' . $externalId;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'x-api-key: ' . $this->apiKey
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            throw new Exception("External API Error: " . curl_error($ch));
        }
        curl_close($ch);

        return [
            'http_code' => $httpCode,
            'response'  => json_decode($response, true),
        ];
    }

    /**
     * Create a domain order directly via NiceNIC API.
     * @param array $postData - domain_name, domain_suffix, vyear, password, contact_*
     * @return array
     */
    public function createDomainOrder($postData)
    {
        if (!isset($postData['domain_name']) || !isset($postData['domain_suffix'])) {
            throw new Exception("Domain name and suffix are required");
        }

        $userid = defined('NICENIC_USER') ? NICENIC_USER : '';
        $pass = defined('NICENIC_PASS') ? NICENIC_PASS : '';
        $email = defined('NICENIC_EMAIL') ? NICENIC_EMAIL : '';
        $baseUrl = defined('NICENIC_API_BASE') ? NICENIC_API_BASE : 'http://api.nicenic.net/v2/';

        $fields = [
            'category' => 'domain',
            'action' => 'activate',
            'domain' => $postData['domain_name'],
            'productid' => ltrim($postData['domain_suffix'], '.'),
            'vyear' => $postData['vyear'] ?? 1,
            'domainpwd' => $postData['password'] ?? 'Default1234',
            'dns1' => $postData['dns1'] ?? 'ns1.ndns.cn',
            'dns2' => $postData['dns2'] ?? 'ns2.ndns.cn',
            // Registrant contact
            'name' => $postData['contact_name'] ?? 'Domain Admin',
            'organization' => $postData['contact_org'] ?? '',
            'address' => $postData['contact_address'] ?? 'N/A',
            'city' => $postData['contact_city'] ?? 'N/A',
            'state' => $postData['contact_state'] ?? 'N/A',
            'postcode' => $postData['contact_postcode'] ?? '00000',
            'country' => $postData['contact_country'] ?? 'US',
            'phone' => $postData['contact_phone'] ?? '+1.5555555555',
            'fax' => $postData['contact_fax'] ?? '+1.5555555555',
            'email' => $postData['contact_email'] ?? $email,
            // Admin / tech / billing same as registrant
            'admin_same_as' => 'reg',
            'tech_same_as' => 'reg',
            'bill_same_as' => 'reg',
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $baseUrl);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: ' . $userid . ':' . $pass,
            'Content-Type: application/json'
        ]);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            throw new Exception("NiceNIC API Error: " . curl_error($ch));
        }
        curl_close($ch);

        return [
            'response' => json_decode($response, true),
            'raw_response' => $response
        ];

    }


    /**
     * Create a domain order with the external provider
     */
    public function createDomainOrderProvider($postData)
    {
        $defaults = [
            'user_id' => $this->userId,
            'auto_renew' => 0
        ];

        $postData = array_merge($defaults, $postData);

        $url = $this->baseUrl . '/orders/create_order';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded',
            'x-api-key: ' . $this->apiKey
        ]);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            throw new Exception("External API Error: " . curl_error($ch));
        }
        curl_close($ch);

        return json_decode($response, true);
    }

    /**
     * Check if a domain is available.
     * @param string $query - The domain name to check (without extension)
     * @param string $suffixes - The domain extension (e.g., 'com', 'io')
     * @return array
     */
    public function checkDomainAvailability($query, $suffixes)
    {
        $url = $this->baseUrl . '/domains/check_availability';

        $postData = [
            'query' => $query,
            'suffixes' => $suffixes,
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData)); // The curl example used standard JSON data body not form-url-encoded
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'x-api-key: ' . $this->apiKey // Assuming we still need the API key even though the curl example didn't explicitly show it in the headers list but usually it's required for auth. Wait, the curl example had cookies but no x-api-key. The other methods use x-api-key. I will stick to x-api-key as per other methods, or maybe I should check if I need to replicate the cookies? The user said "ahi te pase el curl", it includes cookies. usually API keys are preferred if available. The other methods use x-api-key. I'll stick to consistency with x-api-key but user provided curl has cookies. The user provided curl has "Cookie: PHPSESSID=...". This looks like a browser session. I should probably trust the existing `ExternalApiService` pattern which uses `x-api-key`. If that fails, I'll need to ask. But wait, the curl body is JSON. The other methods used `application/x-www-form-urlencoded`. The user's curl specifically says `Content-Type: application/json`. So I must use JSON encoding for the body.
        ]);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            throw new Exception("External API Error: " . curl_error($ch));
        }
        curl_close($ch);

        return [
            'response' => json_decode($response, true),
            'raw_response' => $response
        ];
    }

    /**
     * Reinstall a server with a new operating system.
     * @param string $serverId - The server's external ID
     * @param string $osId - The operating system ID to install
     * @return array
     */
    public function reinstallServer($serverId, $osId = null, $applicationId = null)
    {
        $param = $applicationId ? 'application_id=' . $applicationId : 'os_id=' . $osId;
        $url = $this->baseUrl . '/services/reinstall_server?server_id=' . $serverId . '&' . $param;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'x-api-key: ' . $this->apiKey
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);


        if (curl_errno($ch)) {
            throw new Exception("External API Error: " . curl_error($ch));
        }
        curl_close($ch);

        $jsonResponse = json_decode($response, true);

        return [
            'http_code' => $httpCode,
            'response' => $jsonResponse,
            'raw_response' => $response
        ];
    }

    /**
     * Create addon order (e.g., IP address)
     * POST https://CHANGE_ME/API/orders/create_order_addon
     * 
     * @param string $serverId External server ID
     * @param string $addonId External addon ID (e.g., IP addon type ID)
     * @param string $typePayment Payment type (default: 'balance')
     * @return array
     */
    public function createAddonOrder($serverId, $addonId, $typePayment = 'balance')
    {
        $url = $this->baseUrl . '/orders/create_order_addon';

        $postData = http_build_query([
            'user_id' => $this->userId,
            'server_id' => $serverId,
            'addon_id' => $addonId,
            'type_payment' => $typePayment
        ]);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded',
            'x-api-key: ' . $this->apiKey
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            throw new Exception("External API Error: " . curl_error($ch));
        }
        curl_close($ch);

        $jsonResponse = json_decode($response, true);

        return [
            'http_code' => $httpCode,
            'response' => $jsonResponse,
            'raw_response' => $response
        ];
    }

    /**
     * List all addons for a specific server
     * GET https://CHANGE_ME/API/servers/list_addon_server?server_id=xxx
     * 
     * @param string $serverId External server ID
     * @return array
     */
    public function listServerAddons($serverId)
    {
        $url = $this->baseUrl . '/servers/list_addon_server?server_id=' . $serverId;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'x-api-key: ' . $this->apiKey
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            throw new Exception("External API Error: " . curl_error($ch));
        }
        curl_close($ch);

        $jsonResponse = json_decode($response, true);

        return [
            'http_code' => $httpCode,
            'response' => $jsonResponse,
            'raw_response' => $response
        ];
    }

    /**
     * Get abuse reports for a specific server.
     * @param string $serverId - The server's external ID
     * @param string|null $ip - Optional IP to filter by
     * @return array
     */
    public function getAbuseReports($serverId, $ip = null)
    {
        $url = $this->baseUrl . '/servers/abuses_reports?server_id=' . urlencode($serverId);
        if ($ip) {
            $url .= '&ip=' . urlencode($ip);
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'x-api-key: ' . $this->apiKey
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            throw new Exception("External API Error: " . curl_error($ch));
        }
        curl_close($ch);

        return [
            'http_code' => $httpCode,
            'response'  => json_decode($response, true),
            'raw_response' => $response,
        ];
    }

    /**
     * Change nameservers for a domain
     * POST https://CHANGE_ME/API/domains/change_nameserver
     * 
     * @param string $domain Domain name (e.g., example.com)
     * @param string $dns1 First nameserver
     * @param string $dns2 Second nameserver
     * @return array
     */
    public function changeNameservers($domain, $dns1, $dns2)
    {
        $url = $this->baseUrl . '/domains/change_nameserver';

        $postData = json_encode([
            'domain' => $domain,
            'dns1' => $dns1,
            'dns2' => $dns2
        ]);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'x-api-key: ' . $this->apiKey
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            throw new Exception("External API Error: " . curl_error($ch));
        }
        curl_close($ch);

        $jsonResponse = json_decode($response, true);

        return [
            'http_code' => $httpCode,
            'response' => $jsonResponse,
            'raw_response' => $response
        ];
    }
}
