<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class MpesaService
{
    private function maskSensitive(array $payload): array
    {
        $masked = $payload;
        foreach (['Password', 'SecurityCredential', 'access_token'] as $key) {
            if (isset($masked[$key])) {
                $masked[$key] = '***';
            }
        }
        return $masked;
    }
    /**
     * Get M-Pesa configuration from environment/config only.
     */
    public function getConfig(): array
    {
        $environment = config('mpesa.environment', 'sandbox');
        $callbackUrl = config('mpesa.callback_url');

        $config = [
            'consumer_key' => config('mpesa.consumer_key'),
            'consumer_secret' => config('mpesa.consumer_secret'),
            'passkey' => config('mpesa.passkey'),
            'shortcode' => config('mpesa.shortcode'),
            'c2b_shortcode' => config('mpesa.c2b_shortcode'),
            'party_b' => config('mpesa.party_b'),
            'environment' => $environment,
        ];

        if (empty($callbackUrl)) {
            try {
                $callbackUrl = route('mpesa.callback');
            } catch (\Exception $e) {
                $callbackUrl = url('/mpesa/callback');
            }
        }

        $confirmationUrl = config('mpesa.confirmation_url');
        $validationUrl = config('mpesa.validation_url');
        if (empty($confirmationUrl)) {
            try {
                $confirmationUrl = route('mpesa.callback');
            } catch (\Exception $e) {
                $confirmationUrl = url('/mpesa/callback');
            }
        }
        if (empty($validationUrl)) {
            try {
                $validationUrl = route('mpesa.callback');
            } catch (\Exception $e) {
                $validationUrl = url('/mpesa/callback');
            }
        }

        $config['callback_url'] = $callbackUrl;
        $config['confirmation_url'] = $confirmationUrl;
        $config['validation_url'] = $validationUrl;
        $config['base_url'] = config("mpesa.base_urls.{$environment}", config('mpesa.base_urls.sandbox'));
        $config['timeout'] = config('mpesa.timeout', 30);
        $config['transaction_type'] = config('mpesa.transaction_type', 'CustomerPayBillOnline');

        return $config;
    }

    /**
     * Get base URL based on environment
     */
    public function getBaseUrl(): string
    {
        $config = $this->getConfig();
        return $config['base_url'];
    }

    /**
     * Generate Access Token
     * 
     * @throws \Exception
     */
    public function getAccessToken(): string
    {
        $config = $this->getConfig();
        
        // Validate credentials are configured
        if (empty($config['consumer_key']) || empty($config['consumer_secret'])) {
            Log::error('M-Pesa Credentials Not Configured', [
                'consumer_key_set' => !empty($config['consumer_key']),
                'consumer_secret_set' => !empty($config['consumer_secret']),
            ]);
            throw new \Exception('M-Pesa credentials are not configured. Please set MPESA_CONSUMER_KEY and MPESA_CONSUMER_SECRET in your .env file.');
        }

        // Try to get from cache first (tokens expire after 1 hour)
        $cacheKey = 'mpesa_access_token_' . $config['environment'];
        $cachedToken = Cache::get($cacheKey);
        
        if ($cachedToken) {
            return $cachedToken;
        }

        $baseUrl = $this->getBaseUrl();
        $url = $baseUrl . config('mpesa.endpoints.oauth') . '?grant_type=client_credentials';
        
        try {
            $response = Http::timeout($config['timeout'])
                ->withBasicAuth($config['consumer_key'], $config['consumer_secret'])
                ->get($url);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['access_token'])) {
                    $token = $data['access_token'];
                    
                    // Cache the token for 55 minutes (tokens expire after 1 hour)
                    Cache::put($cacheKey, $token, now()->addMinutes(55));
                    
                    return $token;
                }
                
                Log::error('M-Pesa Access Token Response Missing Token', [
                    'response' => $data,
                    'status' => $response->status(),
                ]);
                throw new \Exception('M-Pesa API returned invalid response. Access token not found.');
            }

            // Log detailed error information
            $errorData = [
                'status' => $response->status(),
                'response' => $response->json(),
                'body' => $response->body(),
            ];
            
            Log::error('M-Pesa Access Token Error', $errorData);
            
            $errorMessage = 'Failed to get M-Pesa access token';
            if ($response->status() === 401) {
                $errorMessage = 'Invalid M-Pesa credentials. Please check your consumer key and secret.';
            } elseif ($response->status() === 0 || $response->status() === null) {
                $errorMessage = 'Unable to connect to M-Pesa API. Please check your internet connection and try again.';
            } else {
                $errorMessage = 'M-Pesa API error: ' . ($response->json()['error_description'] ?? $response->json()['errorMessage'] ?? 'Unknown error');
            }
            
            throw new \Exception($errorMessage);
            
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('M-Pesa Connection Error', [
                'error' => $e->getMessage(),
                'url' => $url,
            ]);
            throw new \Exception('Unable to connect to M-Pesa API. Please check your internet connection.');
        } catch (\Exception $e) {
            // Re-throw if already our custom exception
            if (strpos($e->getMessage(), 'M-Pesa') !== false) {
                throw $e;
            }
            
            Log::error('M-Pesa Access Token Exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw new \Exception('Failed to get M-Pesa access token: ' . $e->getMessage());
        }
    }

    /**
     * Initiate STK Push
     * 
     * @param string $phoneNumber Phone number in format 254XXXXXXXXX
     * @param float $amount Amount to charge
     * @param string $accountReference Account reference
     * @param string|null $transactionDesc Transaction description
     * @return array
     * @throws \Exception
     */
    public function initiateStkPush(string $phoneNumber, float $amount, string $accountReference, ?string $transactionDesc = null): array
    {
        $config = $this->getConfig();
        
        // Validate all required credentials
        $requiredFields = ['shortcode', 'passkey'];
        $missingFields = [];
        foreach ($requiredFields as $field) {
            if (empty($config[$field])) {
                $missingFields[] = 'MPESA_' . strtoupper($field);
            }
        }
        
        if (!empty($missingFields)) {
            throw new \Exception('M-Pesa configuration incomplete. Please configure: ' . implode(', ', $missingFields));
        }

        $accessToken = $this->getAccessToken();
        $baseUrl = $this->getBaseUrl();
        $url = $baseUrl . config('mpesa.endpoints.stk_push');

        // Format phone number
        $phone = $this->formatPhoneNumber($phoneNumber);

        // Generate timestamp
        $timestamp = date('YmdHis');

        // Generate password
        $password = base64_encode($config['shortcode'] . $config['passkey'] . $timestamp);

        $payload = [
            'BusinessShortCode' => $config['shortcode'],
            'Password' => $password,
            'Timestamp' => $timestamp,
            'TransactionType' => $config['transaction_type'],
            'Amount' => (int) $amount,
            'PartyA' => $phone,
            'PartyB' => $config['party_b'] ?: $config['shortcode'],
            'PhoneNumber' => $phone,
            'CallBackURL' => $config['callback_url'],
            'AccountReference' => $accountReference,
            'TransactionDesc' => $transactionDesc ?? 'Payment for order',
        ];

        try {
            Log::info('M-Pesa STK Push Request Payload', [
                'url' => $url,
                'payload' => $this->maskSensitive($payload),
            ]);

            [$response, $responseData] = $this->sendStkPushRequest($url, $accessToken, $config['timeout'], $payload);

            if ($response->successful() && isset($responseData['ResponseCode']) && $responseData['ResponseCode'] == 0) {
                return [
                    'success' => true,
                    'checkout_request_id' => $responseData['CheckoutRequestID'],
                    'customer_message' => $responseData['CustomerMessage'],
                    'merchant_request_id' => $responseData['MerchantRequestID'] ?? null,
                    'response_code' => $responseData['ResponseCode'],
                    'response_description' => $responseData['ResponseDescription'] ?? null,
                ];
            }

            // Auto-retry once with alternate transaction type for common 2029 mismatch issues.
            $resultCode = (string) ($responseData['ResultCode'] ?? '');
            if ($resultCode === '2029') {
                $altType = $payload['TransactionType'] === 'CustomerPayBillOnline'
                    ? 'CustomerBuyGoodsOnline'
                    : 'CustomerPayBillOnline';

                $retryPayload = $payload;
                $retryPayload['TransactionType'] = $altType;

                Log::warning('M-Pesa STK Push Retrying With Alternate TransactionType', [
                    'original_transaction_type' => $payload['TransactionType'],
                    'retry_transaction_type' => $altType,
                    'result_code' => $resultCode,
                    'result_desc' => $responseData['ResultDesc'] ?? null,
                ]);

                [$retryResponse, $retryData] = $this->sendStkPushRequest($url, $accessToken, $config['timeout'], $retryPayload);
                Log::info('M-Pesa STK Push Retry Payload', [
                    'url' => $url,
                    'payload' => $this->maskSensitive($retryPayload),
                ]);
                if ($retryResponse->successful() && isset($retryData['ResponseCode']) && $retryData['ResponseCode'] == 0) {
                    return [
                        'success' => true,
                        'checkout_request_id' => $retryData['CheckoutRequestID'],
                        'customer_message' => $retryData['CustomerMessage'],
                        'merchant_request_id' => $retryData['MerchantRequestID'] ?? null,
                        'response_code' => $retryData['ResponseCode'],
                        'response_description' => $retryData['ResponseDescription'] ?? null,
                    ];
                }
            }

            Log::error('M-Pesa STK Push API Error', [
                'url' => $url,
                'status' => $response->status(),
                'payload' => $this->maskSensitive($payload),
                'response' => $responseData,
                'raw_body' => $response->body(),
            ]);

            throw new \Exception($responseData['errorMessage'] ?? $responseData['ResponseDescription'] ?? 'Failed to initiate STK Push');

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('M-Pesa STK Push Connection Error', [
                'error' => $e->getMessage(),
                'url' => $url,
            ]);
            throw new \Exception('Unable to connect to M-Pesa API. Please check your internet connection.');
        } catch (\Exception $e) {
            Log::error('M-Pesa STK Push Error', [
                'error' => $e->getMessage(),
                'payload' => $this->maskSensitive($payload),
            ]);
            throw $e;
        }
    }

    private function sendStkPushRequest(string $url, string $accessToken, int $timeout, array $payload): array
    {
        $response = Http::timeout($timeout)
            ->withToken($accessToken)
            ->withHeaders([
                'Content-Type' => 'application/json',
            ])
            ->post($url, $payload);

        return [$response, $response->json()];
    }

    /**
     * Query STK Push Status
     * 
     * @param string $checkoutRequestId Checkout request ID from STK Push
     * @return array
     * @throws \Exception
     */
    public function queryStkPushStatus(string $checkoutRequestId): array
    {
        $config = $this->getConfig();
        $accessToken = $this->getAccessToken();
        $baseUrl = $this->getBaseUrl();
        $url = $baseUrl . config('mpesa.endpoints.stk_push_query');

        $timestamp = date('YmdHis');
        $password = base64_encode($config['shortcode'] . $config['passkey'] . $timestamp);

        $payload = [
            'BusinessShortCode' => $config['shortcode'],
            'Password' => $password,
            'Timestamp' => $timestamp,
            'CheckoutRequestID' => $checkoutRequestId,
        ];

        try {
            Log::info('M-Pesa STK Query Request Payload', [
                'url' => $url,
                'payload' => $this->maskSensitive($payload),
            ]);

            $response = Http::timeout($config['timeout'])
                ->withToken($accessToken)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                ])
                ->post($url, $payload);

            $responseData = $response->json();

            if ($response->successful()) {
                if (($responseData['ResultCode'] ?? null) !== null && (int) $responseData['ResultCode'] !== 0 && (int) $responseData['ResultCode'] !== 1032) {
                    Log::warning('M-Pesa STK Query Non-Success Result', [
                        'url' => $url,
                        'status' => $response->status(),
                        'payload' => $this->maskSensitive($payload),
                        'response' => $responseData,
                        'raw_body' => $response->body(),
                    ]);
                }
                return [
                    'success' => true,
                    'result_code' => $responseData['ResultCode'] ?? null,
                    'result_desc' => $responseData['ResultDesc'] ?? null,
                    'checkout_request_id' => $responseData['CheckoutRequestID'] ?? null,
                    'merchant_request_id' => $responseData['MerchantRequestID'] ?? null,
                    'data' => $responseData,
                ];
            }

            Log::error('M-Pesa STK Query API Error', [
                'url' => $url,
                'status' => $response->status(),
                'payload' => $this->maskSensitive($payload),
                'response' => $responseData,
                'raw_body' => $response->body(),
            ]);

            throw new \Exception($responseData['errorMessage'] ?? 'Failed to query STK Push status');

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('M-Pesa Query Connection Error', [
                'error' => $e->getMessage(),
                'url' => $url,
            ]);
            throw new \Exception('Unable to connect to M-Pesa API. Please check your internet connection.');
        } catch (\Exception $e) {
            Log::error('M-Pesa Query Error', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Register C2B validation and confirmation URLs.
     *
     * @throws \Exception
     */
    public function registerC2BUrls(?string $responseType = 'Completed'): array
    {
        $config = $this->getConfig();
        $accessToken = $this->getAccessToken();
        // Use the same environment/base URL path as STK Push.
        $baseUrl = $this->getBaseUrl();
        $url = $baseUrl . config('mpesa.endpoints.c2b_register_url');

        $payload = [
            'ShortCode' => $config['c2b_shortcode'] ?: $config['shortcode'],
            'ResponseType' => $responseType ?: 'Completed',
            'ConfirmationURL' => $config['confirmation_url'],
            'ValidationURL' => $config['validation_url'],
        ];

        try {
            Log::info('M-Pesa C2B Register URL Request Payload', [
                'url' => $url,
                'payload' => $this->maskSensitive($payload),
            ]);

            $response = Http::timeout($config['timeout'])
                ->withToken($accessToken)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                ])
                ->post($url, $payload);

            $responseData = $response->json();

            if ($response->successful()) {
                if (($responseData['ResponseCode'] ?? null) !== null && (int) $responseData['ResponseCode'] !== 0) {
                    Log::warning('M-Pesa C2B Register Non-Success Result', [
                        'url' => $url,
                        'status' => $response->status(),
                        'payload' => $this->maskSensitive($payload),
                        'response' => $responseData,
                        'raw_body' => $response->body(),
                    ]);
                }
                return [
                    'success' => true,
                    'data' => $responseData,
                ];
            }

            Log::error('M-Pesa C2B Register API Error', [
                'url' => $url,
                'status' => $response->status(),
                'payload' => $this->maskSensitive($payload),
                'response' => $responseData,
                'raw_body' => $response->body(),
            ]);

            throw new \Exception($responseData['errorMessage'] ?? $responseData['ResponseDescription'] ?? 'Failed to register C2B URLs');
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('M-Pesa C2B Register URL Connection Error', [
                'error' => $e->getMessage(),
                'url' => $url,
            ]);
            throw new \Exception('Unable to connect to M-Pesa API. Please check your internet connection.');
        } catch (\Exception $e) {
            Log::error('M-Pesa C2B Register URL Error', [
                'error' => $e->getMessage(),
                'payload' => $payload,
            ]);
            throw $e;
        }
    }

    /**
     * Format phone number to 254XXXXXXXXX format
     * 
     * @param string $phone Phone number in various formats
     * @return string Formatted phone number
     */
    public function formatPhoneNumber(string $phone): string
    {
        // Remove any spaces or special characters
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Convert to 254 format if needed
        if (strlen($phone) == 9) {
            // 9 digits starting with 7 (e.g., 712345678)
            return '254' . $phone;
        } elseif (strlen($phone) == 10 && substr($phone, 0, 1) == '0') {
            // Starts with 0, remove and add 254 (e.g., 0712345678)
            return '254' . substr($phone, 1);
        } elseif (substr($phone, 0, 4) == '+254') {
            // Already has +254, remove +
            return substr($phone, 1);
        } elseif (substr($phone, 0, 3) == '254') {
            // Already in correct format
            return $phone;
        }
        
        return $phone;
    }

    /**
     * Validate M-Pesa configuration
     * 
     * @return array Array with 'valid' boolean and 'errors' array
     */
    public function validateConfig(): array
    {
        $config = $this->getConfig();
        $errors = [];

        if (empty($config['consumer_key'])) {
            $errors[] = 'MPESA_CONSUMER_KEY is not configured';
        }

        if (empty($config['consumer_secret'])) {
            $errors[] = 'MPESA_CONSUMER_SECRET is not configured';
        }

        if (empty($config['passkey'])) {
            $errors[] = 'MPESA_PASSKEY is not configured';
        }

        if (empty($config['shortcode'])) {
            $errors[] = 'MPESA_SHORTCODE is not configured';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }
}

