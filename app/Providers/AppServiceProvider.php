<?php

namespace App\Providers;

use App\Events\TicketCreated;
use App\Events\TicketReplied;
use App\Listeners\AnalyzeSentiment;
use App\Listeners\GenerateAIResponse;
use App\Listeners\SendTicketNotification;
use App\Models\KnowledgeArticle;
use App\Models\Ticket;
use App\Models\User;
use App\Policies\KnowledgeArticlePolicy;
use App\Policies\TicketPolicy;
use App\Services\AI\CircuitBreaker;
use App\Services\AI\Clients\GeminiApiClient;
use App\Services\AI\Contracts\AIServiceInterface;
use App\Services\AI\GeminiService;
use App\Services\AI\ResponseParser;
use App\Services\AI\RetryHandler;
use App\Services\AI\UsageTracker;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(GeminiApiClient::class, function () {
            return new GeminiApiClient(config('gemini.providers.gemini'));
        });

        $this->app->singleton(ResponseParser::class);
        $this->app->singleton(CircuitBreaker::class);
        $this->app->singleton(\App\Services\AI\PromptBuilder::class);
        $this->app->singleton(RetryHandler::class, function () {
            return new RetryHandler(config('gemini.providers.gemini'));
        });
        $this->app->singleton(UsageTracker::class);

        $this->app->bind(AIServiceInterface::class, GeminiService::class);
    }

    public function boot(): void
    {
        Gate::policy(Ticket::class, TicketPolicy::class);
        Gate::policy(KnowledgeArticle::class, KnowledgeArticlePolicy::class);

        Gate::before(function (User $user, string $ability) {
            if ($user->isAdmin()) {
                return true;
            }

            return null;
        });

        Event::listen(
            TicketCreated::class,
            GenerateAIResponse::class,
        );

        Event::listen(
            TicketCreated::class,
            SendTicketNotification::class,
        );

        Event::listen(
            TicketReplied::class,
            AnalyzeSentiment::class,
        );
    }
}
