<?php

declare(strict_types=1);

use Laravel\Ai\Provider;

// Managed via Filament: Settings > AI (org-overridable via SettingsOverlayServiceProvider)
return [

    /*
    |--------------------------------------------------------------------------
    | Default AI Provider Names
    |--------------------------------------------------------------------------
    |
    | Here you may specify which of the AI providers below should be the
    | default for AI operations when no explicit provider is provided
    | for the operation. This should be any provider defined below.
    |
    */

    // All AI traffic via OpenRouter (OpenAI and other models). See AI-PROVIDERS.md.
    'default' => 'openrouter',
    'default_for_images' => 'openrouter',
    'default_for_audio' => 'openrouter',
    'default_for_transcription' => 'openrouter',
    'default_for_embeddings' => 'openrouter_embeddings',
    'default_for_reranking' => 'cohere',

    /*
    |--------------------------------------------------------------------------
    | Caching
    |--------------------------------------------------------------------------
    |
    | Below you may configure caching strategies for AI related operations
    | such as embedding generation. You are free to adjust these values
    | based on your application's available caching stores and needs.
    |
    */

    'caching' => [
        'embeddings' => [
            'cache' => false,
            'store' => 'database',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | AI Providers
    |--------------------------------------------------------------------------
    |
    | Below are each of your AI providers defined for this application. Each
    | represents an AI provider and API key combination which can be used
    | to perform tasks like text, image, and audio creation via agents.
    |
    */

    'providers' => [
        'anthropic' => [
            'driver' => 'anthropic',
            'key' => null,
        ],

        'cohere' => [
            'driver' => 'cohere',
            'key' => null,
        ],

        'eleven' => [
            'driver' => 'eleven',
            'key' => null,
        ],

        'gemini' => [
            'driver' => 'gemini',
            'key' => null,
        ],

        'groq' => [
            'driver' => 'groq',
            'key' => null,
        ],

        'jina' => [
            'driver' => 'jina',
            'key' => null,
        ],

        'openai' => [
            'driver' => 'openai',
            'key' => env('OPENAI_API_KEY'),
        ],

        'openrouter' => [
            'driver' => 'openrouter',
            'key' => mb_trim((string) env('OPENROUTER_API_KEY', '')),
            'models' => [
                'text' => [
                    'default' => 'openai/gpt-4o-mini',
                ],
                'embeddings' => [
                    'default' => 'openai/text-embedding-3-small',
                ],
            ],
        ],

        // Embeddings via OpenRouter only (no OpenAI key). Uses OPENROUTER_API_KEY.
        'openrouter_embeddings' => [
            'driver' => 'openrouter_embeddings',
            'key' => mb_trim((string) env('OPENROUTER_API_KEY', '')),
            'models' => [
                'embeddings' => [
                    'default' => 'openai/text-embedding-3-small',
                    'dimensions' => 1536,
                ],
            ],
        ],

        'xai' => [
            'driver' => 'xai',
            'key' => null,
        ],
    ],

];
