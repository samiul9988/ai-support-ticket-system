<?php

namespace App\Providers;

use App\Repositories\Contracts\TicketRepositoryInterface;
use App\Repositories\Contracts\TicketReplyRepositoryInterface;
use App\Repositories\Contracts\TicketCategoryRepositoryInterface;
use App\Repositories\Eloquent\TicketRepository;
use App\Repositories\Eloquent\TicketReplyRepository;
use App\Repositories\Eloquent\TicketCategoryRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(TicketRepositoryInterface::class, TicketRepository::class);
        $this->app->bind(TicketReplyRepositoryInterface::class, TicketReplyRepository::class);
        $this->app->bind(TicketCategoryRepositoryInterface::class, TicketCategoryRepository::class);
    }
}
