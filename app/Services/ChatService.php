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
        return "感谢您的咨询！您可能想了解：\n\n"
            . "1. 鲜花如何保鲜？\n"
            . "2. 玫瑰的花语是什么？\n"
            . "3. 如何订花？\n"
            . "4. 配送范围和时间？\n\n"
            . "请告诉我您想了解的具体问题，我会尽力为您解答~ 🌸";
    }
}
