<?php

namespace App\Repositories\Eloquent;

use App\Models\TicketCategory;
use App\Repositories\Contracts\TicketCategoryRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TicketCategoryRepository implements TicketCategoryRepositoryInterface
{
    public function all(): Collection
    {
        return TicketCategory::with(['children'])
            ->parentOnly()
            ->orderBy('name')
            ->get();
    }

    public function active(): Collection
    {
        return TicketCategory::with(['children'])
            ->active()
            ->parentOnly()
            ->orderBy('name')
            ->get();
    }

    public function find(int $id): ?TicketCategory
    {
        return TicketCategory::with(['children', 'parent'])->find($id);
    }

    public function create(array $data): TicketCategory
    {
        return DB::transaction(function () use ($data) {
            return TicketCategory::create($data);
        });
    }

    public function update(TicketCategory $category, array $data): TicketCategory
    {
        return DB::transaction(function () use ($category, $data) {
            $category->update($data);
            $category->refresh();
            $category->load(['children', 'parent']);

            return $category;
        });
    }

    public function delete(TicketCategory $category): bool
    {
        return DB::transaction(function () use ($category) {
            return $category->delete() ?? false;
        });
    }
}
