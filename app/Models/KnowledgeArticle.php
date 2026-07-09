<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class KnowledgeArticle extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'content',
        'category_id',
        'is_published',
        'view_count',
        'helpful_count',
        'not_helpful_count',
        'meta_keywords',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'view_count' => 'integer',
            'helpful_count' => 'integer',
            'not_helpful_count' => 'integer',
            'meta_keywords' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $article) {
            if (empty($article->slug)) {
                $article->slug = static::generateUniqueSlug($article->title);
            }
        });

        static::updating(function (self $article) {
            if ($article->isDirty('title') && ! $article->isDirty('slug')) {
                $article->slug = static::generateUniqueSlug($article->title, $article->id);
            }
        });
    }

    protected static function generateUniqueSlug(string $title, ?int $excludeId = null): string
    {
        $slug = Str::slug($title);
        $originalSlug = $slug;
        $counter = 1;

        while (true) {
            $query = self::where('slug', $slug);
            if ($excludeId) {
                $query->where('id', '!=', $excludeId);
            }
            if (! $query->exists()) {
                break;
            }
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(TicketCategory::class, 'category_id');
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeDraft($query)
    {
        return $query->where('is_published', false);
    }

    public function scopeByCategory($query, int $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopeSearch($query, string $term)
    {
        $words = array_filter(explode(' ', trim($term)), fn ($w) => strlen($w) > 1);

        if (empty($words)) {
            return $query;
        }

        return $query->where(function ($q) use ($words) {
            foreach ($words as $word) {
                $pattern = '%' . $word . '%';
                $q->where(function ($sub) use ($pattern) {
                    $sub->where('title', 'like', $pattern)
                        ->orWhere('content', 'like', $pattern)
                        ->orWhere('meta_keywords', 'like', $pattern);
                });
            }
        });
    }

    public function scopeFindRelevant($query, string $text, ?int $categoryId = null, int $limit = 5)
    {
        $words = $this->extractKeywords($text);

        if (empty($words)) {
            return $query->published()->limit($limit);
        }

        return $query->published()
            ->when($categoryId, fn ($q) => $q->where('category_id', $categoryId))
            ->where(function ($q) use ($words) {
                foreach ($words as $word) {
                    $q->orWhere('title', 'like', "%{$word}%")
                      ->orWhere('content', 'like', "%{$word}%")
                      ->orWhere('meta_keywords', 'like', "%{$word}%");
                }
            })
            ->orderByDesc('helpful_count')
            ->orderByDesc('view_count')
            ->limit($limit);
    }

    public function scopeRelevantToTicket($query, Ticket $ticket, int $limit = 5)
    {
        $searchText = $ticket->title . ' ' . $ticket->description;

        return $query->findRelevant($searchText, $ticket->category_id, $limit);
    }

    public static function retrieveForRag(string $question, ?int $categoryId = null, int $limit = 5): array
    {
        $articles = static::published()
            ->when($categoryId, fn ($q) => $q->where('category_id', $categoryId))
            ->get();

        if ($articles->isEmpty()) {
            return [];
        }

        $words = static::extractKeywordsStatic($question);

        if (empty($words)) {
            return $articles->take($limit)
                ->map(fn ($a) => static::formatRagResult($a, 0.1))
                ->toArray();
        }

        $scored = $articles->map(function ($article) use ($words) {
            $score = static::calculateRelevanceScore(
                title: $article->title,
                content: $article->content,
                keywords: $article->meta_keywords ?? [],
                searchWords: $words,
            );

            return [
                'article' => $article,
                'score' => $score,
            ];
        })
        ->filter(fn ($r) => $r['score'] > 0)
        ->sortByDesc('score')
        ->take($limit);

        return $scored->map(function ($r) {
            return static::formatRagResult($r['article'], $r['score']);
        })->values()->toArray();
    }

    protected static function formatRagResult(self $article, float $score): array
    {
        return [
            'id' => $article->id,
            'title' => $article->title,
            'content' => $article->content,
            'slug' => $article->slug,
            'category_id' => $article->category_id,
            'score' => round($score, 3),
            'helpful_count' => $article->helpful_count,
            'view_count' => $article->view_count,
        ];
    }

    protected static function calculateRelevanceScore(
        string $title,
        string $content,
        array $keywords,
        array $searchWords,
    ): float {
        $score = 0;
        $titleLower = strtolower($title);
        $contentLower = strtolower($content);
        $keywordLower = array_map('strtolower', $keywords);
        $totalWords = count($searchWords);

        foreach ($searchWords as $word) {
            $word = strtolower($word);

            if ($word === '') {
                continue;
            }

            if (str_contains($titleLower, $word)) {
                $score += 3;  // Title matches are strongest
            }

            if (str_contains($contentLower, $word)) {
                $score += 1;  // Content matches
            }

            foreach ($keywordLower as $kw) {
                if (str_contains($kw, $word) || str_contains($word, $kw)) {
                    $score += 2;  // Keyword tag matches
                    break;
                }
            }
        }

        return $score / max($totalWords * 3, 1);
    }

    protected static function extractKeywordsStatic(string $text): array
    {
        $text = strtolower($text);
        $text = preg_replace('/[^\w\s]/', ' ', $text);
        $words = array_filter(explode(' ', $text), fn ($w) => strlen($w) > 3);

        $stopWords = ['this', 'that', 'with', 'from', 'have', 'been', 'when', 'what', 'your',
            'will', 'they', 'about', 'there', 'their', 'which', 'after', 'before', 'please',
            'would', 'could', 'should', 'thank', 'thanks', 'hello'];

        return array_unique(array_values(array_diff($words, $stopWords)));
    }

    public function incrementViewCount(): void
    {
        $this->increment('view_count');
    }

    public function markHelpful(): void
    {
        $this->increment('helpful_count');
    }

    public function markNotHelpful(): void
    {
        $this->increment('not_helpful_count');
    }

    protected function extractKeywords(string $text): array
    {
        $text = strtolower($text);
        $text = preg_replace('/[^\w\s]/', ' ', $text);
        $words = array_filter(explode(' ', $text), fn ($w) => strlen($w) > 3);

        $stopWords = ['this', 'that', 'with', 'from', 'have', 'been', 'when', 'what', 'your',
            'will', 'they', 'about', 'there', 'their', 'which', 'after', 'before'];

        $words = array_diff($words, $stopWords);

        return array_unique(array_values($words));
    }
}
