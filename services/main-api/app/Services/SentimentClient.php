<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Throwable;

class SentimentClient
{
    public function analyze(?string $text): ?array
    {
        if (! $text || trim($text) === '') {
            return null;
        }

        try {
            $response = Http::timeout(5)->acceptJson()
                ->post(config('services.microservices.sentiment').'/api/analyze', ['text' => $text]);

            if ($response->successful()) {
                return [
                    'sentiment' => $response->json('label'),
                    'sentiment_score' => $response->json('score'),
                ];
            }
        } catch (Throwable $e) {
            report($e);
        }

        return null;
    }
}
