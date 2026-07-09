<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\TicketCategoryResource;
use App\Repositories\Contracts\TicketCategoryRepositoryInterface;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class TicketCategoryController extends Controller
{
    use ApiResponse;

    public function __construct(protected TicketCategoryRepositoryInterface $categoryRepository) {}

    public function index(): JsonResponse
    {
        $categories = $this->categoryRepository->active();

        return $this->success(TicketCategoryResource::collection($categories));
    }

    public function show(int $id): JsonResponse
    {
        $category = $this->categoryRepository->find($id);

        if (! $category) {
            return $this->notFound('Category not found');
        }

        return $this->success(new TicketCategoryResource($category));
    }
}
