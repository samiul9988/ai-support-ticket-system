<?php

namespace App\Http\Requests\Knowledge;

use Illuminate\Foundation\Http\FormRequest;

class UpdateKnowledgeArticleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $articleId = $this->route('id');

        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:knowledge_articles,slug,' . $articleId],
            'content' => ['sometimes', 'string', 'min:20'],
            'category_id' => ['nullable', 'integer', 'exists:ticket_categories,id'],
            'is_published' => ['boolean'],
            'meta_keywords' => ['nullable', 'array'],
            'meta_keywords.*' => ['string', 'max:50'],
        ];
    }
}
