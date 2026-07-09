<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\KnowledgeArticle;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class KnowledgeArticleController extends Controller
{
    use ApiResponse;

    public function index(): JsonResponse
    {
        $articles = KnowledgeArticle::published()
            ->with('category')
            ->latest()
            ->paginate(20);

        return $this->success($articles);
    }

    public function show(int $id): JsonResponse
    {
        $article = KnowledgeArticle::published()->with('category')->find($id);

        if (! $article) {
            return $this->notFound('Article not found');
        }

        return $this->success($article);
    }
}
