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

    /**
     * Maximum knowledge items to load per chat request.
     */
    private const MAX_KNOWLEDGE_ITEMS = 20;

    /**
     * Handle chat message with local knowledge base.
     */
    public function chat(Request $request): JsonResponse
    {
        $key = 'chat:' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 10)) {
            $retryAfter = RateLimiter::availableIn($key);
            return response()->json([
                'error' => 'Too many requests. Please wait.',
                'retry_after' => $retryAfter,
            ], 429);
        }
        RateLimiter::hit($key, 60);

        $request->validate([
            'message' => 'required|string|max:500',
        ]);

        $message = $request->input('message');
        $query = '%' . mb_strtolower($message) . '%';

        // Use DB-level LIKE with limit — no PHP loops, no full table scan
        $relevantKnowledge = Knowledge::where(function ($q) use ($query) {
            $q->whereRaw('LOWER(question) LIKE ?', [$query])
              ->orWhereRaw('LOWER(answer) LIKE ?', [$query]);
        })->limit(self::MAX_KNOWLEDGE_ITEMS)->get(['id', 'question', 'answer']);

        $reply = $this->generateReply($relevantKnowledge);

        return $this->success([
            'reply' => $reply,
            'sources' => $relevantKnowledge->pluck('question')->toArray(),
        ]);
    }

    /**
     * Get knowledge base for context.
     */
    public function knowledge(Request $request): JsonResponse
    {
        $perPage = min((int) $request->get('per_page', 20), 100);
        $page = max((int) $request->get('page', 1), 1);

        $knowledge = Knowledge::query()
            ->orderBy('id', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        return $this->success([
            'data' => $knowledge->items(),
            'pagination' => [
                'current_page' => $knowledge->currentPage(),
                'last_page' => $knowledge->lastPage(),
                'per_page' => $knowledge->perPage(),
                'total' => $knowledge->total(),
            ],
        ]);
    }

    /**
     * Generate reply from knowledge items.
     */
    private function generateReply($knowledge)
    {
        if ($knowledge->isEmpty()) {
            return "抱歉，我没有找到相关信息。";
        }

        $first = $knowledge->first();
        $excerpt = mb_substr($first->answer ?? $first->question, 0, 200);

        return "找到以下信息：{$excerpt}" . (mb_strlen($first->answer ?? '') > 200 ? '...' : '');
    }
}
