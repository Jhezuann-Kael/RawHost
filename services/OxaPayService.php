<?php
require_once __DIR__ . '/../api/config.php';

class OxaPayService
{
    private $apiKey;
    private $baseUrl = 'https://api.oxapay.com/v1';

    public function __construct()
    {
        $this->apiKey = OXAPAY_API_KEY;
    }

    private function request($endpoint, $data = [])
    {
        $url = $this->baseUrl . $endpoint;

        $headers = [
            'Content-Type: application/json'
        ];

        // Some endpoints might require API key in header, others in body.
        // For 'payment/white-label' it is in the header 'merchant_api_key' if using that endpoint?
        // The user snippet shows 'merchant_api_key' in header. 
        // Let's assume standard behavior or follow the snippet.

        // Snippet: 
        // --header 'merchant_api_key: ...'
        // --data-raw '...'

        $headers[] = 'merchant_api_key: ' . $this->apiKey;

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        if (!empty($data)) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            throw new Exception('Curl error: ' . curl_error($ch));
        }

        curl_close($ch);

        $result = json_decode($response, true);

        if (!$result) {
            throw new Exception('Invalid JSON response from OxaPay');
        }

        return $result;
    }

    public function getCurrencies()
    {
        // Snippet: https://api.oxapay.com/v1/common/currencies
        // This seems to be a GET or POST? The snippet used curl --location which defaults to GET if no data, but usually API docs specify.
        // Assuming GET/POST. Since request() handles POST if data present.
        // If it's a GET request, we shouldn't send POST data.

        // Let's modify request() slightly to handle GET if needed or just use empty POST if the API supports it.
        // User snippet: "curl --location 'https://api.oxapay.com/v1/common/currencies'" implies GET.

        $ch = curl_init($this->baseUrl . '/common/currencies');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        curl_close($ch);

        return json_decode($result, true);
    }

    public function createPayment($amount, $currency, $payCurrency, $network, $orderId, $email, $description)
    {
        // Snippet: https://api.oxapay.com/v1/payment/white-label
        $data = [
            'amount' => $amount,
            'currency' => $currency, // e.g. 'EUR' or 'USD'
            'pay_currency' => $payCurrency, // e.g. 'TRX'
            'network' => $network, // e.g. 'TRC20'
            'lifetime' => 60, // 60 minutes
            'fee_paid_by_payer' => 1,
            'under_paid_coverage' => 0, // strict
            'to_currency' => 'USDT', // Or keep as is? User didn't strictly specify, but user snippet has "to_currency": "USDT".
            // If the merchant balance is in USDT, likely we want to convert to USDT or just keep in original.
            // For now, let's omit 'to_currency' unless required, or default to USDT based on user snippet.
            // User snippet: "to_currency": "USDT"
            'auto_withdrawal' => false,
            'callback_url' => 'https://rawhost.net/api/webhook/oxapay', // TODO: Need a real callback URL
            'email' => $email,
            'order_id' => $orderId,
            'description' => $description
        ];

        // Add 'to_currency' if it's user preference for the merchant wallet
        $data['to_currency'] = 'USDT';

        return $this->request('/payment/white-label', $data);
    }
}
