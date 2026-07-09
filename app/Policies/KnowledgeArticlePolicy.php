<?php

namespace App\Policies;

use App\Models\KnowledgeArticle;
use App\Models\User;

class KnowledgeArticlePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, KnowledgeArticle $article): bool
    {
        if ($article->is_published) {
            return true;
        }

        return $user->isAdmin() || $user->hasPermission('knowledge.manage');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('knowledge.manage');
    }

    public function update(User $user, KnowledgeArticle $article): bool
    {
        return $user->hasPermission('knowledge.manage');
    }

    public function delete(User $user, KnowledgeArticle $article): bool
    {
        return $user->hasPermission('knowledge.manage');
    }

    public function publish(User $user, KnowledgeArticle $article): bool
    {
        return $user->hasPermission('knowledge.manage');
    }
}
