<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

/**
 * Chat service handling business logic.
 * Extracted from ChatController to fix God Object violation.
 */
class ChatService
{
    private const CACHE_TTL_SECONDS = 300; // 5 minutes

    /** Fallback response suggestions shown when no knowledge match is found */
    private const FALLBACK_SUGGESTIONS = [
        '鲜花如何保鲜？',
        '玫瑰的花语是什么？',
        '如何订花？',
        '配送范围和时间？',
    ];

    private const FALLBACK_GREETING = '感谢您的咨询！您可能想了解：';
    private const FALLBACK_CLOSING = "请告诉我您想了解的具体问题，我会尽力为您解答~ 🌸";

    private KnowledgeSearchService $searchService;

    public function __construct(KnowledgeSearchService $searchService)
    {
        $this->searchService = $searchService;
    }

    /**
     * Process a chat message and return the best response.
     */
    public function processMessage(string $message): array
    {
        $answer = $this->searchService->search($message);

        if ($answer !== null) {
            return ['reply' => $answer];
        }

        return ['reply' => $this->getFallbackResponse()];
    }

    /**
     * Get knowledge items for client-side caching.
     */
    public function getKnowledgeForClient(): array
    {
        return Cache::remember('knowledge_list', self::CACHE_TTL_SECONDS, function () {
            return $this->searchService->getAllSortedByCategory();
        })->toArray();
    }

    /**
     * Get fallback response when no match is found.
     */
    private function getFallbackResponse(): string
    {
        $lines = [self::FALLBACK_GREETING, ''];
        foreach (self::FALLBACK_SUGGESTIONS as $i => $suggestion) {
            $lines[] = ($i + 1) . '. ' . $suggestion;
        }
        $lines[] = '';
        $lines[] = self::FALLBACK_CLOSING;

        return implode("\n", $lines);
    }
}
