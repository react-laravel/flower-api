<?php

namespace App\Services;

use App\Models\Knowledge;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Knowledge base search service.
 * Extracted from ChatController to fix God Object violation.
 */
class KnowledgeSearchService
{
    private const CACHE_TTL_SECONDS = 300; // 5 minutes
    private const CACHE_KEY_ALL = 'knowledge_all';
    private const CACHE_KEY_LIST = 'knowledge_list';

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
     */
    public function findBestMatch(string $query): ?array
    {
        $knowledgeItems = Cache::remember(self::CACHE_KEY_ALL, self::CACHE_TTL_SECONDS, function () {
            return Knowledge::all();
        });

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
     */
    public function getAllSortedByCategory(): Collection
    {
        return Cache::remember(self::CACHE_KEY_LIST, self::CACHE_TTL_SECONDS, function () {
            return Knowledge::orderBy('category')->get();
        });
    }
}
