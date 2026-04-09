<?php

namespace App\Services\WhatsApp;

class WhatsAppWebhookParser
{
    public function parse(array $payload): array
    {
        $results = [];

        foreach ($payload['entry'] ?? [] as $entry) {
            foreach ($entry['changes'] ?? [] as $change) {
                $value = $change['value'] ?? [];

                // Handle message status updates
                foreach ($value['statuses'] ?? [] as $status) {
                    $results[] = [
                        'type' => 'status',
                        'wamid' => $status['id'],
                        'status' => $status['status'],
                        'timestamp' => $status['timestamp'],
                        'phone_number_id' => $value['metadata']['phone_number_id'] ?? null,
                        'recipient_phone' => $status['recipient_id'] ?? null,
                    ];
                }

                // Handle incoming messages
                foreach ($value['messages'] ?? [] as $message) {
                    // Reactions are handled separately — they update an existing message, not create a new one
                    if (($message['type'] ?? '') === 'reaction') {
                        $results[] = [
                            'type'               => 'reaction',
                            'from'               => $message['from'],
                            'phone_number_id'    => $value['metadata']['phone_number_id'] ?? null,
                            'target_wamid'       => $message['reaction']['message_id'] ?? null,
                            'emoji'              => $message['reaction']['emoji'] ?? '',
                        ];
                        continue;
                    }

                    $parsed = [
                        'type' => 'message',
                        'wamid' => $message['id'],
                        'from' => $message['from'],
                        'timestamp' => $message['timestamp'],
                        'phone_number_id' => $value['metadata']['phone_number_id'] ?? null,
                        'contact_name' => $this->extractContactName($value, $message['from']),
                        'message_type' => $message['type'] ?? 'text',
                        'content' => null,
                        'media_id' => null,
                        'media_mime_type' => null,
                        'media_caption' => null,
                        'reply_to_wamid' => $message['context']['id'] ?? null,
                    ];

                    switch ($message['type']) {
                        case 'text':
                            $parsed['content'] = $message['text']['body'] ?? null;
                            break;
                        case 'image':
                            $parsed['media_id'] = $message['image']['id'] ?? null;
                            $parsed['media_mime_type'] = $message['image']['mime_type'] ?? 'image/jpeg';
                            $parsed['media_caption'] = $message['image']['caption'] ?? null;
                            break;
                        case 'audio':
                            $parsed['media_id'] = $message['audio']['id'] ?? null;
                            $parsed['media_mime_type'] = $message['audio']['mime_type'] ?? 'audio/ogg';
                            break;
                        case 'video':
                            $parsed['media_id'] = $message['video']['id'] ?? null;
                            $parsed['media_mime_type'] = $message['video']['mime_type'] ?? 'video/mp4';
                            $parsed['media_caption'] = $message['video']['caption'] ?? null;
                            break;
                        case 'document':
                            $parsed['media_id'] = $message['document']['id'] ?? null;
                            $parsed['media_mime_type'] = $message['document']['mime_type'] ?? 'application/pdf';
                            $parsed['content'] = $message['document']['filename'] ?? null;
                            break;
                        case 'location':
                            $parsed['content'] = json_encode([
                                'latitude' => $message['location']['latitude'] ?? null,
                                'longitude' => $message['location']['longitude'] ?? null,
                                'name' => $message['location']['name'] ?? null,
                            ]);
                            break;
                        case 'sticker':
                            $parsed['media_id'] = $message['sticker']['id'] ?? null;
                            $parsed['media_mime_type'] = $message['sticker']['mime_type'] ?? 'image/webp';
                            break;
                        default:
                            $parsed['message_type'] = 'unsupported';
                    }

                    $results[] = $parsed;
                }
            }
        }

        return $results;
    }

    private function extractContactName(array $value, string $from): ?string
    {
        $contacts = $value['contacts'] ?? [];
        foreach ($contacts as $contact) {
            if (($contact['wa_id'] ?? '') === $from) {
                return $contact['profile']['name'] ?? null;
            }
        }
        return null;
    }
}
