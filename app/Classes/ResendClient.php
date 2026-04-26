<?php

namespace App\Classes;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Thin HTTP client for Resend (https://resend.com).
 *
 * For most use cases, prefer Laravel Mailables + Mail::to(...)->send(...) — that
 * uses the SMTP transport configured in .env (MAIL_HOST=smtp.resend.com).
 *
 * This client is for cases where you need direct API access (custom payloads,
 * batch sends, scheduled emails, etc.).
 */
class ResendClient
{
    private const API_BASE = 'https://api.resend.com';

    private static ?self $instance = null;

    public static function getInstance(): self
    {
        return self::$instance ??= new self();
    }

    /**
     * Send a transactional email through Resend's API.
     *
     * @param  string|array  $to        Single email or array of emails
     * @param  string        $subject
     * @param  string        $html      HTML body
     * @param  string|null   $text      Optional plain-text body
     * @param  string|null   $from      Override sender (defaults to env MAIL_FROM_ADDRESS)
     * @param  array         $extra     Additional Resend fields (cc, bcc, reply_to, attachments, tags, headers)
     * @return array{ok:bool, id:?string, error:?string, status:int}
     */
    public function send(
        string|array $to,
        string $subject,
        string $html,
        ?string $text = null,
        ?string $from = null,
        array $extra = []
    ): array {
        $apiKey = env('RESEND_API_KEY');

        if (empty($apiKey)) {
            return ['ok' => false, 'id' => null, 'error' => 'RESEND_API_KEY not set', 'status' => 0];
        }

        $payload = array_merge([
            'from'    => $from ?? config('mail.from.address') ?? env('MAIL_FROM_ADDRESS', 'info@kamgus.com'),
            'to'      => is_array($to) ? $to : [$to],
            'subject' => $subject,
            'html'    => $html,
        ], $extra);

        if ($text !== null) {
            $payload['text'] = $text;
        }

        try {
            $response = Http::withToken($apiKey)
                ->acceptJson()
                ->asJson()
                ->timeout(15)
                ->post(self::API_BASE . '/emails', $payload);

            if ($response->successful()) {
                return [
                    'ok'     => true,
                    'id'     => $response->json('id'),
                    'error'  => null,
                    'status' => $response->status(),
                ];
            }

            Log::warning('Resend API failure', [
                'status' => $response->status(),
                'body'   => $response->body(),
                'to'     => $to,
            ]);

            return [
                'ok'     => false,
                'id'     => null,
                'error'  => $response->json('message') ?? $response->body(),
                'status' => $response->status(),
            ];
        } catch (\Throwable $e) {
            Log::error('Resend client exception', ['exception' => $e->getMessage(), 'to' => $to]);
            return ['ok' => false, 'id' => null, 'error' => $e->getMessage(), 'status' => 0];
        }
    }
}
