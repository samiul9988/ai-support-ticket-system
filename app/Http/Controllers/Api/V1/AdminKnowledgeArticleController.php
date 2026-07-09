<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Knowledge\StoreKnowledgeArticleRequest;
use App\Http\Requests\Knowledge\UpdateKnowledgeArticleRequest;
use App\Models\KnowledgeArticle;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminKnowledgeArticleController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        if ($request->user()->cannot('viewAny', KnowledgeArticle::class)) {
            return $this->forbidden();
        }

        $query = KnowledgeArticle::with('category');

        if ($request->has('search')) {
            $query->search($request->input('search'));
        }

        if ($request->has('status')) {
            match ($request->input('status')) {
                'published' => $query->published(),
                'draft' => $query->draft(),
                default => null,
            };
        }

        $articles = $query->latest()->paginate($request->input('per_page', 20));

        return $this->success($articles);
    }

    public function store(StoreKnowledgeArticleRequest $request): JsonResponse
    {
        if ($request->user()->cannot('create', KnowledgeArticle::class)) {
            return $this->forbidden();
        }

        $article = KnowledgeArticle::create($request->validated());

        return $this->created($article->load('category'), 'Article created successfully');
    }

    public function show(int $id, Request $request): JsonResponse
    {
        $article = KnowledgeArticle::with('category')->find($id);

        if (! $article) {
            return $this->notFound('Article not found');
        }

        if ($request->user()->cannot('view', $article)) {
            return $this->forbidden();
        }

        $article->incrementViewCount();

        return $this->success($article);
    }

    public function update(int $id, UpdateKnowledgeArticleRequest $request): JsonResponse
    {
        $article = KnowledgeArticle::find($id);

        if (! $article) {
            return $this->notFound('Article not found');
        }

        if ($request->user()->cannot('update', $article)) {
            return $this->forbidden();
        }

        $article->update($request->validated());

        return $this->success($article->fresh()->load('category'), 'Article updated successfully');
    }

    public function destroy(int $id, Request $request): JsonResponse
    {
        $article = KnowledgeArticle::find($id);

        if (! $article) {
            return $this->notFound('Article not found');
        }

        if ($request->user()->cannot('delete', $article)) {
            return $this->forbidden();
        }

        $article->delete();

        return $this->success(null, 'Article deleted successfully');
    }

    public function publish(int $id, Request $request): JsonResponse
    {
        $article = KnowledgeArticle::find($id);

        if (! $article) {
            return $this->notFound('Article not found');
        }

        if ($request->user()->cannot('publish', $article)) {
            return $this->forbidden();
        }

        $article->update(['is_published' => true]);

        return $this->success($article->fresh(), 'Article published successfully');
    }

    public function unpublish(int $id, Request $request): JsonResponse
    {
        $article = KnowledgeArticle::find($id);

        if (! $article) {
            return $this->notFound('Article not found');
        }

        if ($request->user()->cannot('publish', $article)) {
            return $this->forbidden();
        }

        $article->update(['is_published' => false]);

        return $this->success($article->fresh(), 'Article unpublished successfully');
    }
}
