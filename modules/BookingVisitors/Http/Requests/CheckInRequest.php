<?php

namespace Modules\BookingVisitors\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckInRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() ? auth()->user()->allowedTo('nexopos.bookingvisitors.checkin') : true;
    }

    public function rules(): array
    {
        return [
            'token' => ['required', 'string', 'min:20', 'max:255'],
            'source' => ['nullable', 'string', 'max:50'],
        ];
    }
}

