<?php

namespace App\Http\Requests\Knowledge;

use Illuminate\Foundation\Http\FormRequest;

class StoreKnowledgeArticleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:knowledge_articles,slug'],
            'content' => ['required', 'string', 'min:20'],
            'category_id' => ['nullable', 'integer', 'exists:ticket_categories,id'],
            'is_published' => ['boolean'],
            'meta_keywords' => ['nullable', 'array'],
            'meta_keywords.*' => ['string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'content.min' => 'Article content must be at least 20 characters.',
        ];
    }
}
