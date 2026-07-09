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
