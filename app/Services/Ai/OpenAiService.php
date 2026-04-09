<?php

namespace App\Services\Ai;

use App\Models\Setting;
use Illuminate\Support\Facades\Log;
use OpenAI;

class OpenAiService
{
    private string $model;
    private int $maxTokens;
    private float $temperature;

    public function __construct()
    {
        $this->model = Setting::get('ai', 'model', 'gpt-4o-mini');
        $this->maxTokens = (int) Setting::get('ai', 'max_tokens', 1000);
        $this->temperature = (float) Setting::get('ai', 'temperature', 0.7);
    }

    private function client(): \OpenAI\Client
    {
        $apiKey = Setting::get('ai', 'api_key') ?: config('openai.api_key', '');

        return OpenAI::client($apiKey);
    }

    public function generateReply(array $messages): array
    {
        try {
            $response = $this->client()->chat()->create([
                'model' => $this->model,
                'messages' => $messages,
                'max_tokens' => $this->maxTokens,
                'temperature' => $this->temperature,
            ]);

            $content = $response->choices[0]->message->content ?? '';
            $tokensUsed = $response->usage->totalTokens ?? 0;

            return [
                'content' => $content,
                'tokens_used' => $tokensUsed,
                'success' => true,
            ];
        } catch (\Exception $e) {
            Log::error('OpenAI API error', ['error' => $e->getMessage()]);
            return [
                'content' => null,
                'tokens_used' => 0,
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function buildSystemPrompt(string $basePrompt, array $customerContext = []): string
    {
        $prompt = $basePrompt;

        if (!empty($customerContext)) {
            $prompt .= "\n\nCustomer Information:\n";
            foreach ($customerContext as $key => $value) {
                if ($value) {
                    $prompt .= "- {$key}: {$value}\n";
                }
            }
        }

        $prompt .= "\n\nRespond naturally and helpfully. Keep responses concise and relevant to sales inquiries.";

        return $prompt;
    }
}
