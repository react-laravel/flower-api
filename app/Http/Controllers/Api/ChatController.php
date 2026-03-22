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

    private const SCORE_EXACT = 100;
    private const SCORE_CONTAINS = 80;
    private const SCORE_KEYWORD_MAX = 60;
    private const SCORE_THRESHOLD = 20;

    private const DEFAULT_REPLY = "感谢您的咨询！您可能想了解：\n\n1. 鲜花如何保鲜？\n2. 玫瑰的花语是什么？\n3. 如何订花？\n4. 配送范围和时间？\n\n请告诉我您想了解的具体问题，我会尽力为您解答~ 🌸";

    public function chat(Request $request): JsonResponse
    {
        $request->validate([
            'message' => 'required|string',
        ]);

        $query = strtolower($request->message);
        $bestMatch = null;
        $highestScore = 0;

        $knowledgeItems = Knowledge::all();

        foreach ($knowledgeItems as $item) {
            $score = $this->calculateMatchScore($query, strtolower($item->question));

            if ($score > $highestScore) {
                $highestScore = $score;
                $bestMatch = $item;
            }
        }

        $answer = ($bestMatch && $highestScore > self::SCORE_THRESHOLD)
            ? $bestMatch->answer
            : $this->getDefaultReply();

        return $this->success([
            'reply' => $answer,
        ]);
    }

    private function calculateMatchScore(string $query, string $question): int
    {
        // Exact match
        if ($question === $query) {
            return self::SCORE_EXACT;
        }

        // Contains match
        if (str_contains($question, $query) || str_contains($query, $question)) {
            return self::SCORE_CONTAINS;
        }

        // Keyword match
        $queryWords = explode(' ', preg_replace('/\s+/', ' ', trim($query)));
        $questionWords = explode(' ', preg_replace('/\s+/', ' ', trim($question)));
        $matches = array_filter($queryWords, function ($w) use ($questionWords) {
            return count(array_filter($questionWords, fn($qw) => str_contains($qw, $w) || str_contains($w, $qw))) > 0;
        });

        return (count($matches) / count($queryWords)) * self::SCORE_KEYWORD_MAX;
    }

    private function getDefaultReply(): string
    {
        return self::DEFAULT_REPLY;
    }

    public function knowledge(): JsonResponse
    {
        $knowledge = Knowledge::orderBy('category')->get();

        return $this->success($knowledge);
    }
}
