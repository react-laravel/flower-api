<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\Knowledge;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class ChatController extends Controller
{
    use ApiResponse;

    public function chat(Request $request): JsonResponse
    {
        // Rate limiting: 10 requests per minute per IP
        $key = 'chat:' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 10)) {
            return response()->json([
                'error' => 'Too many requests. Please wait.',
                'retry_after' => RateLimiter::availableIn($key),
            ], 429);
        }
        RateLimiter::hit($key, 60);

        $request->validate([
            'message' => 'required|string|max:500',
        ]);

        $message = $request->input('message');

        // Use keyword search instead of all() to avoid OOM
        $keywords = $this->extractKeywords($message);
        $relevantKnowledge = $this->searchKnowledge($keywords);

        $reply = $this->generateReply($message, $relevantKnowledge);

        return $this->success([
            'reply' => $reply,
            'sources' => $relevantKnowledge->pluck('question')->toArray(),
        ]);
    }

    /**
     * Extract keywords from message.
     */
    private function extractKeywords(string $message): array
    {
        $stopWords = ['the', 'a', 'an', 'is', 'are', 'was', 'were', 'what', 'how', 'why', 'when', 'where', '?'];
        $words = preg_split('/\s+/', strtolower($message));
        return array_filter($words, fn($w) => strlen($w) > 2 && !in_array($w, $stopWords));
    }

    /**
     * Search knowledge base with keywords (paginated/chunked).
     */
    private function searchKnowledge(array $keywords)
    {
        if (empty($keywords)) {
            return collect([]);
        }

        $query = Knowledge::query();
        foreach ($keywords as $keyword) {
            $query->where(function ($q) use ($keyword) {
                $q->where('question', 'like', "%{$keyword}%")
                  ->orWhere('answer', 'like', "%{$keyword}%");
            });
        }

        // Limit results to 20 to avoid large payloads
        return $query->limit(20)->get();
    }

    /**
     * Generate reply based on message and knowledge.
     */
    private function generateReply(string $message, $knowledge)
    {
        if ($knowledge->isEmpty()) {
            return "抱歉，我没有找到相关信息。";
        }

        $first = $knowledge->first();
        $excerpt = mb_substr($first->answer ?? $first->question, 0, 200);

        return "找到以下信息：{$excerpt}...";
    }

    public function knowledge(): JsonResponse
    {
        $knowledge = Knowledge::orderBy('category')->get();

        return $this->success($knowledge);
    }
}
