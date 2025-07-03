<?php

namespace IJIDeals\Internationalization\Services;

use Exception;
use Illuminate\Support\Facades\Log;

interface TranslationProviderInterface
{
    public function translate(string $text, string $from, string $to): ?string;
}

use Illuminate\Support\Facades\Http; // Added for HTTP calls

class GoogleTranslationProvider implements TranslationProviderInterface
{
    public function translate(string $text, string $from, string $to): ?string
    {
        $apiKey = config('internationalization.translation_services.google.api_key');
        $baseUrl = config('internationalization.translation_services.google.base_url');

        if (empty($apiKey) || empty($baseUrl)) {
            Log::error('Google Translate API key or base URL is not configured.');

            return null;
        }

        try {
            $response = Http::get($baseUrl, [
                'key' => $apiKey,
                'q' => $text,
                'source' => $from,
                'target' => $to,
                'format' => 'text', // Ensures plain text translation
            ]);

            if ($response->successful()) {
                $responseData = $response->json();
                if (isset($responseData['data']['translations'][0]['translatedText'])) {
                    return $responseData['data']['translations'][0]['translatedText'];
                }
                Log::error('Google Translate API call succeeded but response format is unexpected.', $responseData ?? []);
            } else {
                Log::error('Google Translate API call failed.', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }
        } catch (Exception $e) {
            Log::error('Exception during Google Translate API call: '.$e->getMessage());
        }

        return null;
    }
}

class DeepLTranslationProvider implements TranslationProviderInterface
{
    public function translate(string $text, string $from, string $to): ?string
    {
        // TODO: Implement DeepL API call
        return null;
    }
}

class AzureTranslationProvider implements TranslationProviderInterface
{
    public function translate(string $text, string $from, string $to): ?string
    {
        // TODO: Implement Azure Translator API call
        return null;
    }
}

class TranslationService
{
    protected TranslationProviderInterface $provider;

    public function __construct()
    {
        $provider = config('internationalization.auto_translation.provider', 'google');
        switch ($provider) {
            case 'deepl':
                $this->provider = new DeepLTranslationProvider;
                break;
            case 'azure':
                $this->provider = new AzureTranslationProvider;
                break;
            case 'google':
            default:
                $this->provider = new GoogleTranslationProvider;
                break;
        }
    }

    public function translate(string $text, string $from, string $to): ?string
    {
        try {
            return $this->provider->translate($text, $from, $to);
        } catch (Exception $e) {
            Log::error('Translation failed: '.$e->getMessage());

            return null;
        }
    }
}
