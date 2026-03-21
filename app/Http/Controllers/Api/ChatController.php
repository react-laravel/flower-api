<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Services\ChatService;
use App\Services\KnowledgeSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Chat controller handling HTTP concerns only.
 * Business logic extracted to ChatService (fixes God Object).
 */
class ChatController extends Controller
{
    use ApiResponse;

    private const QUERY_TIMEOUT_SECONDS = 5;

    private ChatService $chatService;

    public function __construct(KnowledgeSearchService $searchService)
    {
        $this->chatService = new ChatService($searchService);
    }

    /**
     * Chat endpoint with knowledge base search.
     */
    public function chat(Request $request): JsonResponse
    {
        $request->validate([
            'message' => 'required|string|max:500',
        ]);

        // Timeout guard: abort if DB is unresponsive
        DB::connection()->getPdo()->setAttribute(\PDO::ATTR_TIMEOUT, self::QUERY_TIMEOUT_SECONDS);

        $reply = $this->chatService->processMessage($request->message);

        return $this->success($reply);
    }

    /**
     * Get all knowledge items for client-side caching.
     */
    public function knowledge(): JsonResponse
    {
        $knowledge = $this->chatService->getKnowledgeForClient();

        return $this->success($knowledge);
    }
}
