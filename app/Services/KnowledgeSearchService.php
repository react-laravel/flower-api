<?php

namespace App\Services;

use App\Models\Knowledge;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Knowledge base search service.
 * Extracted from ChatController to fix God Object violation.
 * Reliability: query timeout, cache graceful degradation.
 */
class KnowledgeSearchService
{
    private const CACHE_TTL_SECONDS = 300; // 5 minutes
    private const CACHE_KEY_ALL = 'knowledge_all';
    private const CACHE_KEY_LIST = 'knowledge_list';
    private const QUERY_TIMEOUT_SECONDS = 5; // Max seconds for DB query

    private const EXACT_MATCH_SCORE = 100;
    private const CONTAINS_MATCH_SCORE = 80;
    private const KEYWORD_MATCH_MAX_SCORE = 60;
    private const MIN_SCORE_THRESHOLD = 20;

    /**
     * Search knowledge base for best matching answer.
     */
    public function search(string $query): ?string
    {
        $query = strtolower(trim($query));
        $bestMatch = $this->findBestMatch($query);

        if ($bestMatch && $bestMatch['score'] > self::MIN_SCORE_THRESHOLD) {
            return $bestMatch['item']->answer;
        }

        return null;
    }

    /**
     * Find the best matching knowledge item.
     * Uses cache with graceful degradation: falls back to DB on cache failure.
     * DB query is bounded by QUERY_TIMEOUT_SECONDS.
     */
    public function findBestMatch(string $query): ?array
    {
        $knowledgeItems = $this->getAllKnowledgeItems();

        $bestMatch = null;
        $highestScore = 0;

        foreach ($knowledgeItems as $item) {
            $score = $this->calculateScore($query, $item);

            if ($score > $highestScore) {
                $highestScore = $score;
                $bestMatch = $item;
            }
        }

        if ($bestMatch === null) {
            return null;
        }

        return [
            'item' => $bestMatch,
            'score' => $highestScore,
        ];
    }

    /**
     * Get all knowledge items with cache fallback to DB.
     * Cache failure is tolerated — falls back to direct DB query.
     */
    private function getAllKnowledgeItems(): Collection
    {
        try {
            return Cache::remember(self::CACHE_KEY_ALL, self::CACHE_TTL_SECONDS, function () {
                return $this->queryKnowledgeWithTimeout();
            });
        } catch (Throwable $e) {
            // Cache (Redis) unavailable — query DB directly with timeout
            return $this->queryKnowledgeWithTimeout();
        }
    }

    /**
     * Query Knowledge table with a connection timeout.
     * Prevents indefinite hanging on slow/unresponsive DB.
     */
    private function queryKnowledgeWithTimeout(): Collection
    {
        $pdo = DB::connection()->getPdo();
        $pdo->setAttribute(\PDO::ATTR_TIMEOUT, self::QUERY_TIMEOUT_SECONDS);

        return Knowledge::query()->get();
    }

    /**
     * Calculate match score between query and knowledge item.
     */
    private function calculateScore(string $query, Knowledge $item): int
    {
        $question = strtolower($item->question);

        // Exact match
        if ($question === $query) {
            return self::EXACT_MATCH_SCORE;
        }

        // Contains match
        if (str_contains($question, $query) || str_contains($query, $question)) {
            return self::CONTAINS_MATCH_SCORE;
        }

        // Keyword match
        return $this->calculateKeywordScore($query, $question);
    }

    /**
     * Calculate score based on keyword matching.
     */
    private function calculateKeywordScore(string $query, string $question): int
    {
        $queryWords = array_filter(explode(' ', preg_replace('/\s+/', ' ', trim($query))));
        $questionWords = explode(' ', preg_replace('/\s+/', ' ', trim($question)));

        if (count($queryWords) === 0) {
            return 0;
        }

        $matches = array_filter($queryWords, function ($w) use ($questionWords) {
            return count(array_filter($questionWords, fn($qw) => str_contains($qw, $w) || str_contains($w, $qw))) > 0;
        });

        return (count($matches) / count($queryWords)) * self::KEYWORD_MATCH_MAX_SCORE;
    }

    /**
     * Get all knowledge items sorted by category.
     * Graceful degradation: falls back to DB if cache fails.
     */
    public function getAllSortedByCategory(): Collection
    {
        try {
            return Cache::remember(self::CACHE_KEY_LIST, self::CACHE_TTL_SECONDS, function () {
                return Knowledge::orderBy('category')->get();
            });
        } catch (Throwable $e) {
            return Knowledge::orderBy('category')->get();
        }
    }
}
