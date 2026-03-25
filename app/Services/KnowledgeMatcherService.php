<?php

namespace App\Services;

use App\Models\Knowledge;

/**
 * Knowledge base matching algorithm service.
 * Handles keyword scoring and best-match search logic.
 * Extracted from KnowledgeSearchService as a reusable component.
 */
class KnowledgeMatcherService
{
    public const EXACT_MATCH_SCORE = 100;
    public const CONTAINS_MATCH_SCORE = 80;
    public const KEYWORD_MATCH_MAX_SCORE = 60;
    public const MIN_SCORE_THRESHOLD = 20;

    /**
     * Find the best matching knowledge item from a collection.
     *
     * @param string $query
     * @param iterable<Knowledge> $knowledgeItems
     * @return array{item: Knowledge, score: int}|null
     */
    public function findBestMatch(string $query, iterable $knowledgeItems): ?array
    {
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
     * Check if a match meets the minimum score threshold.
     */
    public function isMatchAboveThreshold(array $match): bool
    {
        return ($match['score'] ?? 0) > self::MIN_SCORE_THRESHOLD;
    }

    /**
     * Calculate match score between query and knowledge item.
     */
    public function calculateScore(string $query, Knowledge $item): int
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
     * Handles both English (space-separated words) and Chinese (character-level) text.
     */
    public function calculateKeywordScore(string $query, string $question): int
    {
        // Use character-level matching for Chinese text (no word boundaries),
        // word-level matching for English text (space-separated).
        $queryTokens = $this->tokenize($query);
        $questionTokens = $this->tokenize($question);

        if (count($queryTokens) === 0) {
            return 0;
        }

        $matches = array_filter($queryTokens, function ($token) use ($questionTokens) {
            foreach ($questionTokens as $qt) {
                if (str_contains($qt, $token) || str_contains($token, $qt)) {
                    return true;
                }
            }
            return false;
        });

        return (count($matches) / count($queryTokens)) * self::KEYWORD_MATCH_MAX_SCORE;
    }

    /**
     * Tokenize text for keyword matching.
     * Chinese: split into individual characters (no word boundaries).
     * English: split by whitespace into words.
     *
     * @return array<string>
     */
    public function tokenize(string $text): array
    {
        $text = trim($text);
        if ($text === '') {
            return [];
        }

        // Check if text contains Chinese characters (U+4E00 to U+9FFF range)
        if (preg_match('/[\x{4e00}-\x{9fff}]/u', $text)) {
            // Split Chinese text into individual characters, filter empty
            return array_values(array_filter(mb_str_split($text)));
        }

        // For non-Chinese text, use space-separated words
        return array_values(array_filter(preg_split('/\s+/', $text)));
    }
}
