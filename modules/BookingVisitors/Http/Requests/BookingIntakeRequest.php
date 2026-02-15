<?php

namespace Modules\BookingVisitors\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BookingIntakeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() ? auth()->user()->allowedTo('nexopos.bookingvisitors.api.expose') : true;
    }

    public function rules(): array
    {
        return [
            'channel' => ['required', 'in:phone,website,whatsapp_business_api'],
            'customer_name' => ['required', 'string', 'max:190'],
            'customer_phone' => ['nullable', 'string', 'max:50'],
            'customer_email' => ['nullable', 'email', 'max:190'],
            'start_at' => ['required', 'date'],
            'end_at' => ['required', 'date', 'after:start_at'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}

