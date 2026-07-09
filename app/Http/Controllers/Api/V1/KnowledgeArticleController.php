<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\KnowledgeArticle;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KnowledgeArticleController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $query = KnowledgeArticle::published()->with('category');

        if ($request->has('search')) {
            $query->search($request->input('search'));
        }

        if ($request->has('category_id')) {
            $query->byCategory((int) $request->input('category_id'));
        }

        $articles = $query->latest()->paginate($request->input('per_page', 20));

        return $this->success($articles);
    }

    public function show(int $id): JsonResponse
    {
        $article = KnowledgeArticle::published()->with('category')->find($id);

        if (! $article) {
            return $this->notFound('Article not found');
        }

        $article->incrementViewCount();

        return $this->success($article);
    }

    public function helpful(int $id): JsonResponse
    {
        $article = KnowledgeArticle::published()->find($id);

        if (! $article) {
            return $this->notFound('Article not found');
        }

        $article->markHelpful();

        return $this->success(null, 'Thank you for your feedback');
    }

    public function notHelpful(int $id): JsonResponse
    {
        $article = KnowledgeArticle::published()->find($id);

        if (! $article) {
            return $this->notFound('Article not found');
        }

        $article->markNotHelpful();

        return $this->success(null, 'Thank you for your feedback');
    }
}
