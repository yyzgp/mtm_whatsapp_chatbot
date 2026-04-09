<?php

namespace App\Services;

use App\Models\User;
use Google\Auth\Credentials\ServiceAccountCredentials;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PushNotificationService
{
    private string $projectId;

    public function __construct()
    {
        $this->projectId = config('services.firebase.project_id', '');
    }

    public function sendToUser(User $user, string $title, string $body, array $data = []): void
    {
        if (empty($user->fcm_token)) {
            return;
        }

        $this->send($user->fcm_token, $title, $body, $data);
    }

    public function sendToUsers(array $userIds, string $title, string $body, array $data = []): void
    {
        $tokens = User::whereIn('id', $userIds)
            ->whereNotNull('fcm_token')
            ->pluck('fcm_token')
            ->toArray();

        foreach ($tokens as $token) {
            $this->send($token, $title, $body, $data);
        }
    }

    private function send(string $token, string $title, string $body, array $data = []): void
    {
        $accessToken = $this->getAccessToken();
        if (!$accessToken || !$this->projectId) {
            Log::warning('FCM not configured: missing access token or project ID');
            return;
        }

        try {
            $payload = [
                'message' => [
                    'token' => $token,
                    'notification' => [
                        'title' => $title,
                        'body' => $body,
                    ],
                    'data' => array_map('strval', $data),
                    'android' => [
                        'priority' => 'high',
                        'notification' => [
                            'sound' => 'default',
                            'channel_id' => 'default',
                        ],
                    ],
                    'apns' => [
                        'payload' => [
                            'aps' => [
                                'sound' => 'default',
                                'badge' => 1,
                            ],
                        ],
                    ],
                ],
            ];

            Log::info('FCM sending', [
                'data' => $data,
                'token_prefix' => substr($token, 0, 20) . '...',
            ]);

            $response = Http::withToken($accessToken)
                ->post("https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send", $payload);

            if ($response->successful()) {
                Log::info('FCM sent successfully', ['response' => $response->json()]);
            } else {
                Log::warning('FCM send failed', [
                    'status' => $response->status(),
                    'response' => $response->json(),
                    'token_prefix' => substr($token, 0, 20) . '...',
                ]);
            }
        } catch (\Exception $e) {
            Log::warning('FCM send exception', ['error' => $e->getMessage()]);
        }
    }

    private function getAccessToken(): ?string
    {
        return Cache::remember('fcm_access_token', 3000, function () {
            $credentialsPath = config('services.firebase.credentials');

            if (!$credentialsPath || !file_exists($credentialsPath)) {
                Log::warning('Firebase credentials file not found', ['path' => $credentialsPath]);
                return null;
            }

            $credentials = new ServiceAccountCredentials(
                'https://www.googleapis.com/auth/firebase.messaging',
                json_decode(file_get_contents($credentialsPath), true)
            );

            $token = $credentials->fetchAuthToken();
            return $token['access_token'] ?? null;
        });
    }
}
