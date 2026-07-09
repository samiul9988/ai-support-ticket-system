<?php

namespace App\Http\Requests\Ticket;

use App\Enums\TicketPriority;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'min:10', 'max:10000'],
            'category_id' => ['nullable', 'integer', 'exists:ticket_categories,id'],
            'priority' => ['nullable', new Enum(TicketPriority::class)],
            'source' => ['nullable', 'string', 'in:web,email,api,chat'],
        ];
    }

    public function messages(): array
    {
        return [
            'description.min' => 'Please provide at least 10 characters for the description.',
            'description.max' => 'Description must not exceed 10,000 characters.',
        ];
    }
}
