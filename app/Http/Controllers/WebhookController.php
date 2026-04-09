<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessIncomingWhatsAppMessage;
use App\Models\WhatsAppAccount;
use App\Models\WhatsAppPhoneNumber;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function verify(Request $request): Response
    {
        $mode = $request->query('hub_mode');
        $token = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        $account = WhatsAppAccount::where('verify_token', $token)->where('is_active', true)->first();

        if ($mode === 'subscribe' && $account) {
            Log::info('WhatsApp webhook verified', ['account' => $account->name]);
            return response($challenge, 200);
        }

        return response('Forbidden', 403);
    }

    public function handle(Request $request): Response
    {
        $payload = $request->all();
        Log::info('WhatsApp webhook hit', ['object' => $payload['object'] ?? 'none']);

        if (($payload['object'] ?? '') !== 'whatsapp_business_account') {
            return response('OK', 200);
        }

        // Find the phone number by phone_number_id
        $phoneNumberId = data_get($payload, 'entry.0.changes.0.value.metadata.phone_number_id');
        Log::info('WhatsApp webhook phone lookup', ['phone_number_id' => $phoneNumberId]);
        $phoneNumber = WhatsAppPhoneNumber::where('phone_number_id', $phoneNumberId)
            ->where('is_active', true)
            ->first();

        if (!$phoneNumber) {
            Log::warning('WhatsApp webhook: phone number not found', ['phone_number_id' => $phoneNumberId]);
            return response('OK', 200);
        }

        // Validate signature using the parent account
        if (!$this->validateSignature($request)) {
            Log::warning('WhatsApp webhook: invalid signature');
            return response('Forbidden', 403);
        }

        ProcessIncomingWhatsAppMessage::dispatch($payload, $phoneNumber->id);

        return response('OK', 200);
    }

    private function validateSignature(Request $request): bool
    {
        $appSecret = config('whatsapp.app_secret', '');
        if (!$appSecret) return true; // Skip in dev if not configured

        $signature = $request->header('X-Hub-Signature-256');
        if (!$signature) return false;

        $expected = 'sha256=' . hash_hmac('sha256', $request->getContent(), $appSecret);
        return hash_equals($expected, $signature);
    }
}
