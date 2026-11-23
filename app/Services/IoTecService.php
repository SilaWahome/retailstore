<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class IoTecService
{
    protected string $authUrl;
    protected string $baseUrl;
    protected ?string $clientId;
    protected ?string $clientSecret;
    protected ?string $walletId;

    public function __construct()
    {
        $this->authUrl = (string) (config('services.iotec.auth_url') ?: env('IOTEC_AUTH_URL', env('IOTECH_AUTH_URL', 'https://id.iotec.io/connect/token')));
        $this->baseUrl = rtrim((string) (config('services.iotec.base_url') ?: env('IOTEC_BASE_URL', env('IOTECH_BASE_URL', 'https://pay.iotec.io'))), '/');
        $this->clientId = (string) (config('services.iotec.client_id') ?: env('IOTEC_CLIENT_ID', env('IOTECH_CLIENT_ID', '')));
        $this->clientSecret = (string) (config('services.iotec.client_secret') ?: env('IOTEC_CLIENT_SECRET', env('IOTECH_CLIENT_SECRET', '')));
        $this->walletId = (string) (config('services.iotec.wallet_id') ?: env('IOTEC_WALLET_ID', env('IOTECH_WALLET_ID', '')));
    }

    /**
     * Check if IoTec credentials are configured.
     */
    public function isConfigured(): bool
    {
        return !empty($this->clientId) && !empty($this->clientSecret) && !$this->isDemoKey();
    }

    /**
     * Check if currently using placeholder or demo keys.
     */
    public function isDemoKey(): bool
    {
        return empty($this->clientId) ||
               empty($this->clientSecret) ||
               str_contains($this->clientId, 'sample') ||
               str_contains($this->clientId, 'iotech_key') ||
               str_contains($this->clientSecret, 'sample') ||
               str_contains($this->clientSecret, 'iotech_secret');
    }

    /**
     * Get SSL CA cert verification option for cURL/Guzzle on Windows.
     */
    protected function getVerifyOption()
    {
        $certPath = base_path('public/cacert.pem');
        if (file_exists($certPath)) {
            return $certPath;
        }

        $wampCert = 'C:/wamp64/bin/php/cacert.pem';
        if (file_exists($wampCert)) {
            return $wampCert;
        }

        return app()->environment('local') ? false : true;
    }

    /**
     * Format phone number to standard international Uganda format without plus: 256XXXXXXXXX
     */
    public static function formatUgandanPhone(string $phone): string
    {
        $digits = preg_replace('/[^0-9]/', '', $phone);
        if (empty($digits)) {
            return '256700000000';
        }
        if (str_starts_with($digits, '0') && strlen($digits) === 10) {
            return '256' . substr($digits, 1);
        }
        if (strlen($digits) === 9 && str_starts_with($digits, '7')) {
            return '256' . $digits;
        }
        if (str_starts_with($digits, '256') && strlen($digits) === 12) {
            return $digits;
        }
        return '256' . $digits;
    }

    /**
     * Detect network (MTN or Airtel Uganda) from phone number.
     */
    public static function detectNetwork(string $phone): string
    {
        $formatted = self::formatUgandanPhone($phone);
        // Prefix after 256 is the 4th and 5th digits
        if (strlen($formatted) >= 5 && str_starts_with($formatted, '256')) {
            $prefix = substr($formatted, 3, 2);
            if (in_array($prefix, ['77', '78', '76', '39', '31'])) {
                return 'MTN';
            }
            if (in_array($prefix, ['70', '75', '74'])) {
                return 'Airtel';
            }
        }
        return 'MTN';
    }

    /**
     * Obtain OAuth 2.0 Access Token via client credentials flow.
     */
    public function getAccessToken(): ?string
    {
        if ($this->isDemoKey()) {
            return null;
        }

        $cacheKey = 'iotec_access_token_' . md5($this->clientId);

        return Cache::remember($cacheKey, 240, function () {
            try {
                $verifyOpt = $this->getVerifyOption();

                $response = Http::asForm()
                    ->withOptions(['verify' => $verifyOpt])
                    ->timeout(20)
                    ->post($this->authUrl, [
                        'client_id'     => $this->clientId,
                        'client_secret' => $this->clientSecret,
                        'grant_type'    => 'client_credentials',
                    ]);

                if ($response->successful()) {
                    $data = $response->json();
                    return $data['access_token'] ?? null;
                }

                Log::error('IoTec Auth Failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return null;
            } catch (\Exception $e) {
                Log::error('IoTec Auth Exception: ' . $e->getMessage());
                return null;
            }
        });
    }

    /**
     * Initiate Collection (Mobile Money STK Push to customer phone).
     *
     * @param float $amount
     * @param string $phone
     * @param string $externalId
     * @param string|null $payerName
     * @param string|null $payerNote
     * @param string|null $payeeNote
     * @param string|null $callbackUrl
     * @return array
     */
    public function initiateCollection(
        float $amount,
        string $phone,
        string $externalId,
        ?string $payerName = 'Customer',
        ?string $payerNote = 'Retail POS Mobile Payment',
        ?string $payeeNote = 'Retail Store Checkout',
        ?string $callbackUrl = null
    ): array {
        $formattedPhone = self::formatUgandanPhone($phone);

        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'status'  => 'failed',
                'message' => 'Mobile Money API credentials are not configured in .env. Please set IOTEC_CLIENT_ID, IOTEC_CLIENT_SECRET, and IOTEC_WALLET_ID to prompt the client\'s phone.',
            ];
        }

        $token = $this->getAccessToken();
        if (!$token) {
            return [
                'success' => false,
                'status'  => 'failed',
                'message' => 'Unable to obtain ioTec authorization token. Please check API credentials in settings.',
            ];
        }

        try {
            $verifyOpt = $this->getVerifyOption();
            $endpoint = rtrim($this->baseUrl, '/') . '/api/collections/collect';

            $payload = [
                'category'                   => 'MobileMoney',
                'currency'                   => 'UGX',
                'walletId'                   => $this->walletId ?: '',
                'externalId'                 => $externalId,
                'payer'                      => $formattedPhone,
                'payerName'                  => $payerName ?: 'Customer',
                'payerNote'                  => $payerNote ?: 'Retail POS Mobile Payment',
                'amount'                     => (float) $amount,
                'payeeNote'                  => $payeeNote ?: 'Luxeshop Official POS',
                'redirectUrl'                => $callbackUrl ?: url('/pos'),
                'transactionChargesCategory' => 'ChargeWallet',
            ];

            $response = Http::withToken($token)
                ->withOptions(['verify' => $verifyOpt])
                ->timeout(25)
                ->post($endpoint, $payload);

            $json = $response->json() ?? [];

            if ($response->successful()) {
                $txId = $json['id'] ?? $json['transactionId'] ?? null;
                $reqId = $json['requestId'] ?? null;

                if ($txId) {
                    Cache::put('iotec_tx_' . $externalId, $txId, 86400);
                }

                return [
                    'success'        => true,
                    'status'         => 'pending', // STK prompt pushed, awaiting PIN
                    'request_id'     => $reqId ?: $txId,
                    'transaction_id' => $txId,
                    'external_id'    => $externalId,
                    'message'        => $json['message'] ?? 'STK push prompt sent to customer handset. Please have them enter Mobile Money PIN.',
                    'data'           => $json,
                ];
            }

            $errorMessage = $json['message'] ?? $json['error'] ?? $response->body() ?? 'Failed to prompt mobile money STK push.';
            Log::warning('ioTec Collection Error: ' . $response->body());

            return [
                'success' => false,
                'status'  => 'failed',
                'message' => 'ioTec STK Push Error: ' . $errorMessage,
                'data'    => $json,
            ];
        } catch (\Exception $e) {
            Log::error('ioTec Collection Exception: ' . $e->getMessage());
            return [
                'success' => false,
                'status'  => 'failed',
                'message' => 'API Connection Exception: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Check status of a collection transaction.
     */
    public function getCollectionStatus(string $externalId, ?string $transactionId = null): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'status'  => 'failed',
                'message' => 'Mobile Money gateway credentials not configured.',
            ];
        }

        $token = $this->getAccessToken();
        if (!$token) {
            return ['success' => false, 'status' => 'pending', 'message' => 'Awaiting token'];
        }

        $verifyOpt = $this->getVerifyOption();

        // 1. Try checking by transactionId first (standard ioTec API method as in Gita)
        $actualTxId = $transactionId ?: Cache::get('iotec_tx_' . $externalId);
        if ($actualTxId && !str_starts_with($actualTxId, 'IOTEC-SIM-')) {
            try {
                $statusUrl = rtrim($this->baseUrl, '/') . "/api/collections/status/{$actualTxId}";
                $response = Http::withToken($token)
                    ->withOptions(['verify' => $verifyOpt])
                    ->timeout(15)
                    ->get($statusUrl);

                if ($response->successful()) {
                    $data = $response->json();
                    $rawStatus = strtolower($data['status'] ?? '');
                    if (in_array($rawStatus, ['success', 'successful', 'complete', 'completed', 'paid'])) {
                        return ['success' => true, 'status' => 'complete', 'raw_status' => $rawStatus, 'data' => $data];
                    }
                    if (in_array($rawStatus, ['failed', 'rejected', 'cancelled', 'expired', 'declined'])) {
                        return ['success' => true, 'status' => 'failed', 'raw_status' => $rawStatus, 'data' => $data, 'message' => 'Payment was ' . $rawStatus];
                    }
                    return ['success' => true, 'status' => 'pending', 'raw_status' => $rawStatus, 'data' => $data];
                }
            } catch (\Exception $e) {
                Log::warning('ioTec status by txId error: ' . $e->getMessage());
            }
        }

        // 2. Fallback to external-id endpoint
        try {
            $url = rtrim($this->baseUrl, '/') . '/api/collections/external-id/' . urlencode($externalId);
            $response = Http::withToken($token)
                ->withOptions(['verify' => $verifyOpt])
                ->timeout(15)
                ->get($url);

            if ($response->successful()) {
                $data = $response->json();
                $rawStatus = strtolower($data['status'] ?? '');
                if (in_array($rawStatus, ['success', 'successful', 'complete', 'completed', 'paid'])) {
                    return ['success' => true, 'status' => 'complete', 'raw_status' => $rawStatus, 'data' => $data];
                }
                if (in_array($rawStatus, ['failed', 'rejected', 'cancelled', 'expired', 'declined'])) {
                    return ['success' => true, 'status' => 'failed', 'raw_status' => $rawStatus, 'data' => $data, 'message' => 'Payment was ' . $rawStatus];
                }
                return ['success' => true, 'status' => 'pending', 'raw_status' => $rawStatus, 'data' => $data];
            }
        } catch (\Exception $e) {
            Log::error('ioTec getCollectionStatus by externalId error: ' . $e->getMessage());
        }

        return [
            'success' => false,
            'status'  => 'pending',
            'message' => 'Status check in progress',
        ];
    }

    /**
     * Initiate Disbursement (Payout / Withdrawal to store admin MTN or Airtel number).
     *
     * @param float $amount Amount to disburse (UGX)
     * @param string $phone Recipient MSISDN
     * @param string $externalId
     * @param string|null $payeeName
     * @param string|null $payerNote
     * @param string|null $payeeNote
     * @return array
     */
    public function initiateDisbursement(
        float $amount,
        string $phone,
        string $externalId,
        ?string $payeeName = 'Store Administrator',
        ?string $payerNote = 'Retail Store Withdrawal',
        ?string $payeeNote = 'Merchant Payout'
    ): array {
        $formattedPhone = self::formatUgandanPhone($phone);
        $token = $this->getAccessToken();

        if ($token) {
            try {
                $payload = [
                    'amount' => (float) $amount,
                    'currency' => 'UGX',
                    'externalId' => $externalId,
                    'payee' => $formattedPhone,
                    'payeeName' => $payeeName ?: 'Store Administrator',
                    'payerNote' => $payerNote ?: 'Retail Store Withdrawal',
                    'payeeNote' => $payeeNote ?: 'Merchant Payout',
                ];

                if (!empty($this->walletId)) {
                    $payload['walletId'] = $this->walletId;
                }

                $response = Http::withToken($token)
                    ->timeout(20)
                    ->post(rtrim($this->baseUrl, '/') . '/api/disbursements/disburse', $payload);

                $json = $response->json() ?? [];

                if ($response->successful()) {
                    return [
                        'success' => true,
                        'status' => 'pending',
                        'transaction_id' => $json['transactionId'] ?? $json['id'] ?? null,
                        'external_id' => $externalId,
                        'message' => $json['message'] ?? 'Disbursement submitted successfully. Funds are being sent to ' . $formattedPhone,
                        'data' => $json,
                    ];
                }

                return [
                    'success' => false,
                    'status' => 'failed',
                    'message' => $json['message'] ?? $response->body() ?: 'Disbursement request failed.',
                    'data' => $json,
                ];
            } catch (\Exception $e) {
                Log::error('IoTec Disbursement Error: ' . $e->getMessage());
                return [
                    'success' => false,
                    'status' => 'failed',
                    'message' => 'API Connection Error: ' . $e->getMessage(),
                ];
            }
        }

        // Sandbox / Demonstration Mode
        return [
            'success' => true,
            'status' => 'complete',
            'simulated' => true,
            'transaction_id' => 'DISB-SIM-' . uniqid(),
            'external_id' => $externalId,
            'message' => 'Disbursement of UGX ' . number_format($amount) . ' sent to ' . $formattedPhone . ' (Demo/Simulated Mode).',
            'data' => [
                'payee' => $formattedPhone,
                'amount' => $amount,
                'currency' => 'UGX',
            ],
        ];
    }

    /**
     * Check status of a disbursement transaction.
     */
    public function getDisbursementStatus(string $externalId, ?string $transactionId = null): array
    {
        $token = $this->getAccessToken();

        if ($token) {
            try {
                $url = rtrim($this->baseUrl, '/') . '/api/disbursements/external-id/' . urlencode($externalId);
                $response = Http::withToken($token)->timeout(15)->get($url);

                if ($response->successful()) {
                    $data = $response->json();
                    $rawStatus = strtolower($data['status'] ?? 'pending');
                    $unifiedStatus = 'pending';
                    if (in_array($rawStatus, ['success', 'successful', 'complete', 'completed', 'senttovendor'])) {
                        $unifiedStatus = 'complete';
                    } elseif (in_array($rawStatus, ['failed', 'cancelled', 'rejected'])) {
                        $unifiedStatus = 'failed';
                    }

                    return [
                        'success' => true,
                        'status' => $unifiedStatus,
                        'raw_status' => $rawStatus,
                        'data' => $data,
                    ];
                }
            } catch (\Exception $e) {
                Log::error('IoTec getDisbursementStatus error: ' . $e->getMessage());
            }
        }

        return [
            'success' => true,
            'status' => 'complete',
            'simulated' => true,
        ];
    }
}
