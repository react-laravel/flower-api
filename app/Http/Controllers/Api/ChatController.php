<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\Knowledge;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ChatController extends Controller
{
    use ApiResponse;

    private const CACHE_TTL_SECONDS = 300; // 5 minutes
    private const QUERY_TIMEOUT_SECONDS = 5; // DB query timeout

    /**
     * Chat endpoint with knowledge base search.
     * Uses cache to avoid reloading all knowledge on every request.
     */
    public function chat(Request $request): JsonResponse
    {
        $request->validate([
            'message' => 'required|string|max:500',
        ]);

        // Timeout guard: abort if DB is unresponsive
        DB::connection()->getPdo()->setAttribute(\PDO::ATTR_TIMEOUT, self::QUERY_TIMEOUT_SECONDS);

        $query = strtolower(trim($request->message));
        $bestMatch = null;
        $highestScore = 0;

        // Cache knowledge items for 5 minutes to avoid repeated DB load
        $knowledgeItems = Cache::remember('knowledge_all', self::CACHE_TTL_SECONDS, function () {
            return Knowledge::all();
        });

        foreach ($knowledgeItems as $item) {
            $question = strtolower($item->question);
            $score = 0;

            // Exact match
            if ($question === $query) {
                $score = 100;
            }
            // Contains match
            elseif (str_contains($question, $query) || str_contains($query, $question)) {
                $score = 80;
            }
            // Keyword match
            else {
                $queryWords = array_filter(explode(' ', preg_replace('/\s+/', ' ', trim($query))));
                $questionWords = explode(' ', preg_replace('/\s+/', ' ', trim($question)));
                if (count($queryWords) === 0) {
                    continue;
                }
                $matches = array_filter($queryWords, function ($w) use ($questionWords) {
                    return count(array_filter($questionWords, fn($qw) => str_contains($qw, $w) || str_contains($w, $qw))) > 0;
                });
                $score = (count($matches) / count($queryWords)) * 60;
            }

            if ($score > $highestScore) {
                $highestScore = $score;
                $bestMatch = $item;
            }
        }

        if ($bestMatch && $highestScore > 20) {
            $answer = $bestMatch->answer;
        } else {
            $answer = "感谢您的咨询！您可能想了解：\n\n1. 鲜花如何保鲜？\n2. 玫瑰的花语是什么？\n3. 如何订花？\n4. 配送范围和时间？\n\n请告诉我您想了解的具体问题，我会尽力为您解答~ 🌸";
        }

        return $this->success([
            'reply' => $answer,
        ]);
    }

    /**
     * Get all knowledge items for client-side caching.
     */
    public function knowledge(): JsonResponse
    {
        $knowledge = Cache::remember('knowledge_list', self::CACHE_TTL_SECONDS, function () {
            return Knowledge::orderBy('category')->get();
        });

        return $this->success($knowledge);
    }
}
