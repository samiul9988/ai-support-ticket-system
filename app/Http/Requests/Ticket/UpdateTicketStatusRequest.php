<?php

namespace App\Http\Requests\Ticket;

use App\Enums\TicketStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateTicketStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', new Enum(TicketStatus::class)],
            'note' => ['nullable', 'string', 'max:500'],
        ];
    }
}
