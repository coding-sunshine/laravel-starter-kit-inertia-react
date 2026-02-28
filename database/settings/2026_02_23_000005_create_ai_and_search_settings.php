<?php

declare(strict_types=1);

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $addIfMissing = function (string $key, mixed $value, bool $encrypted = false): void {
            if (! $this->migrator->exists($key)) {
                $encrypted ? $this->migrator->addEncrypted($key, $value) : $this->migrator->add($key, $value);
            }
        };
        // Prism
        $addIfMissing('prism.prism_server_enabled', (bool) config('prism.prism_server.enabled', false));
        $addIfMissing('prism.request_timeout', (int) config('prism.request_timeout', 30));
        $addIfMissing('prism.default_provider', config('prism.defaults.provider', 'openrouter'));
        $addIfMissing('prism.default_model', config('prism.defaults.model', 'deepseek/deepseek-r1-0528:free'));
        $addIfMissing('prism.openai_api_key', config('prism.providers.openai.api_key'), true);
        $addIfMissing('prism.anthropic_api_key', config('prism.providers.anthropic.api_key'), true);
        $addIfMissing('prism.groq_api_key', config('prism.providers.groq.api_key'), true);
        $addIfMissing('prism.xai_api_key', config('prism.providers.xai.api_key'), true);
        $addIfMissing('prism.gemini_api_key', config('prism.providers.gemini.api_key'), true);
        $addIfMissing('prism.deepseek_api_key', config('prism.providers.deepseek.api_key'), true);
        $addIfMissing('prism.mistral_api_key', config('prism.providers.mistral.api_key'), true);
        $addIfMissing('prism.openrouter_api_key', config('prism.providers.openrouter.api_key'), true);
        $addIfMissing('prism.elevenlabs_api_key', config('prism.providers.elevenlabs.api_key'), true);
        $addIfMissing('prism.voyageai_api_key', config('prism.providers.voyageai.api_key'), true);

        // AI
        $addIfMissing('ai.default_provider', config('ai.default', 'openrouter'));
        $addIfMissing('ai.default_for_images', config('ai.default_for_images', 'openrouter'));
        $addIfMissing('ai.default_for_audio', config('ai.default_for_audio', 'openrouter'));
        $addIfMissing('ai.default_for_transcription', config('ai.default_for_transcription', 'openrouter'));
        $addIfMissing('ai.default_for_embeddings', config('ai.default_for_embeddings', 'openrouter_embeddings'));
        $addIfMissing('ai.default_for_reranking', config('ai.default_for_reranking', 'cohere'));
        $addIfMissing('ai.chat_model', config('ai.providers.openrouter.models.text.default'));

        // Scout
        $addIfMissing('scout.driver', config('scout.driver', 'collection'));
        $addIfMissing('scout.prefix', config('scout.prefix', ''));
        $addIfMissing('scout.queue', (bool) config('scout.queue', false));
        $addIfMissing('scout.identify', (bool) config('scout.identify', false));
        $addIfMissing('scout.typesense_api_key', config('scout.typesense.client-settings.api_key'), true);
        $addIfMissing('scout.typesense_host', config('scout.typesense.client-settings.nodes.0.host', 'localhost'));
        $addIfMissing('scout.typesense_port', (int) config('scout.typesense.client-settings.nodes.0.port', 8108));
        $addIfMissing('scout.typesense_protocol', config('scout.typesense.client-settings.nodes.0.protocol', 'http'));

        // Memory
        $addIfMissing('memory.dimensions', (int) config('memory.dimensions', 1536));
        $addIfMissing('memory.similarity_threshold', (float) config('memory.similarity_threshold', 0.5));
        $addIfMissing('memory.recall_limit', (int) config('memory.recall_limit', 10));
        $addIfMissing('memory.middleware_recall_limit', (int) config('memory.middleware_recall_limit', 5));
        $addIfMissing('memory.recall_oversample_factor', (int) config('memory.recall_oversample_factor', 2));
        $addIfMissing('memory.table', config('memory.table', 'memories'));
    }
};
