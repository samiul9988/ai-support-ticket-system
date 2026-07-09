<?php

namespace App\Repositories\Contracts;

use App\Models\TicketCategory;
use Illuminate\Support\Collection;

interface TicketCategoryRepositoryInterface
{
    public function all(): Collection;

    public function active(): Collection;

    public function find(int $id): ?TicketCategory;

    public function create(array $data): TicketCategory;

    public function update(TicketCategory $category, array $data): TicketCategory;

    public function delete(TicketCategory $category): bool;
}
