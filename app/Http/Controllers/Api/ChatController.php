<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Services\ChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Chat controller handling HTTP concerns only.
 * Business logic extracted to ChatService (fixes God Object).
 */
class ChatController extends Controller
{
    use ApiResponse;

    private const QUERY_TIMEOUT_SECONDS = 5;
    private const MAX_MESSAGE_LENGTH = 500;

    private ChatService $chatService;

    public function __construct(ChatService $chatService)
    {
        $this->chatService = $chatService;
    }

    /**
     * Chat endpoint with knowledge base search.
     */
    public function chat(Request $request): JsonResponse
    {
        $request->validate([
            'message' => 'required|string|max:' . self::MAX_MESSAGE_LENGTH,
        ]);

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
