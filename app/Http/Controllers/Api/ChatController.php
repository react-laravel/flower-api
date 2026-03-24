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

    public function chat(Request $request): JsonResponse
    {
        $request->validate([
            'message' => 'required|string',
        ]);

        $query = strtolower($request->message);
        $bestMatch = null;
        $highestScore = 0;

        $knowledgeItems = Knowledge::limit(100)->get();

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
                $queryWords = explode(' ', preg_replace('/\s+/', ' ', trim($query)));
                $questionWords = explode(' ', preg_replace('/\s+/', ' ', trim($question)));
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

    public function knowledge(): JsonResponse
    {
        $knowledge = Knowledge::orderBy('category')->get();

        return $this->success($knowledge);
    }
}
