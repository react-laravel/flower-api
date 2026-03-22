<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\Knowledge;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    use ApiResponse;

    // Chat matching score constants
    private const SCORE_EXACT = 100;
    private const SCORE_CONTAINS = 80;
    private const SCORE_KEYWORD_MAX = 60;
    private const SCORE_THRESHOLD = 20;

    public function chat(Request $request): JsonResponse
    {
        $request->validate([
            'message' => 'required|string',
        ]);

        $queryLower = strtolower($request->message);
        // Pre-split query words once — regex pattern compiled once, not per-iteration
        $queryWords = $this->splitWords($queryLower);

        $bestMatch = null;
        $highestScore = 0;

        $knowledgeItems = Knowledge::all();

        foreach ($knowledgeItems as $item) {
            $questionLower = strtolower($item->question);
            $score = $this->calculateMatchScore($queryLower, $queryWords, $questionLower);

            if ($score > $highestScore) {
                $highestScore = $score;
                $bestMatch = $item;
            }
        }

        if ($bestMatch && $highestScore > self::SCORE_THRESHOLD) {
            $answer = $bestMatch->answer;
        } else {
            $answer = "感谢您的咨询！您可能想了解：\n\n1. 鲜花如何保鲜？\n2. 玫瑰的花语是什么？\n3. 如何订花？\n4. 配送范围和时间？\n\n请告诉我您想了解的具体问题，我会尽力为您解答~ 🌸";
        }

        return $this->success([
            'reply' => $answer,
        ]);
    }

    public function knowledge(): JsonResponse
    {
        $knowledge = Knowledge::orderBy('category')->get();

        return $this->success($knowledge);
    }

    /**
     * Normalize whitespace in a string and split into words.
     * The regex pattern is compiled once per call, not per word.
     */
    private function splitWords(string $text): array
    {
        return explode(' ', preg_replace('/\s+/', ' ', trim($text)));
    }

    /**
     * Calculate match score for a given knowledge item.
     * Returns SCORE_EXACT, SCORE_CONTAINS, or a keyword-based fraction of SCORE_KEYWORD_MAX.
     */
    private function calculateMatchScore(string $queryLower, array $queryWords, string $questionLower): int
    {
        // Exact match
        if ($questionLower === $queryLower) {
            return self::SCORE_EXACT;
        }

        // Contains match
        if (str_contains($questionLower, $queryLower) || str_contains($queryLower, $questionLower)) {
            return self::SCORE_CONTAINS;
        }

        // Keyword match
        $questionWords = $this->splitWords($questionLower);
        $matches = array_filter($queryWords, fn($w) => $this->wordMatches($w, $questionWords));
        $count = count($queryWords);

        return $count > 0 ? (int) round((count($matches) / $count) * self::SCORE_KEYWORD_MAX) : 0;
    }

    /**
     * Check if a word matches any question word (substring match).
     */
    private function wordMatches(string $word, array $questionWords): bool
    {
        foreach ($questionWords as $qw) {
            if (str_contains($qw, $word) || str_contains($word, $qw)) {
                return true;
            }
        }
        return false;
    }
}
