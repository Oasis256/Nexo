<?php

namespace Modules\BookingVisitors\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GuestAccessRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() ? auth()->user()->allowedTo('nexopos.bookingvisitors.guest.access') : true;
    }

    public function rules(): array
    {
        return [
            'token' => ['required', 'string', 'min:20', 'max:255'],
            'guest_name' => ['nullable', 'string', 'max:190'],
        ];
    }
}

