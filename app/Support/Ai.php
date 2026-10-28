<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * ✨ Talks to the AI. It uses whichever key is written in the .env file:
 *   GROQ_API_KEY (Groq) → or XAI_API_KEY (Grok) → or ANTHROPIC_API_KEY (Claude)
 * The key stays on the server, the browser never sees it.
 */
class Ai
{
    /**
     * Which AI service to use, with its address, key and model (or null if there's no key).
     * Groq and Grok both speak the same "chat completions" language, so they share the code.
     */
    public static function provider(): ?array
    {
        if (filled(config('services.groq.key'))) {
            return [
                'name' => 'Groq',
                'url' => 'https://api.groq.com/openai/v1/chat/completions',
                'key' => config('services.groq.key'),
                'model' => config('services.groq.model'),
            ];
        }

        if (filled(config('services.xai.key'))) {
            return [
                'name' => 'Grok',
                'url' => 'https://api.x.ai/v1/chat/completions',
                'key' => config('services.xai.key'),
                'model' => config('services.xai.model'),
            ];
        }

        if (filled(config('services.anthropic.key'))) {
            return [
                'name' => 'Claude',
                'url' => 'https://api.anthropic.com/v1/messages',
                'key' => config('services.anthropic.key'),
                'model' => config('services.anthropic.model'),
            ];
        }

        return null;
    }

    // Is there an AI key in .env?
    public static function enabled(): bool
    {
        return self::provider() !== null;
    }

    /**
     * Send a question to the AI and get the full HTTP answer back (used by ask() and by "php artisan ai:check").
     */
    public static function send(string $instructions, string $text, int $maxTokens = 200)
    {
        $ai = self::provider();

        // 🤖 Claude has its own format
        if ($ai['name'] === 'Claude') {
            return Http::withHeaders(['x-api-key' => $ai['key'], 'anthropic-version' => '2023-06-01'])
                ->timeout(25)
                ->post($ai['url'], [
                    'model' => $ai['model'],
                    'max_tokens' => $maxTokens,
                    'system' => $instructions,
                    'messages' => [['role' => 'user', 'content' => $text]],
                ]);
        }

        // 🤖 Groq and Grok: the "chat completions" format
        return Http::withToken($ai['key'])
            ->timeout(25)
            ->post($ai['url'], [
                'model' => $ai['model'],
                'max_tokens' => $maxTokens,
                'messages' => [
                    ['role' => 'system', 'content' => $instructions],
                    ['role' => 'user', 'content' => $text],
                ],
            ]);
    }

    // Take the text out of the AI's answer
    public static function textOf($response): string
    {
        $text = self::provider()['name'] === 'Claude'
            ? $response->json('content.0.text')
            : $response->json('choices.0.message.content');

        return trim((string) $text);
    }

    /**
     * Send a question to the AI and get its answer as text (or null if something went wrong).
     */
    public static function ask(string $instructions, string $text, int $maxTokens = 200): ?string
    {
        if (! self::enabled()) {
            return null;
        }

        try {
            $response = self::send($instructions, $text, $maxTokens);

            return $response->successful() ? self::textOf($response) : null;
        } catch (Throwable $e) {
            // no internet, wrong key, … → the caller decides what to do
            report($e);

            return null;
        }
    }
}
