<?php

namespace Modules\NsMultiStore\Exceptions;

use Exception;

class NotAllowedStoreAccessException extends Exception
{
    public function render($request)
    {
        if (! $request->expectsJson()) {
            return response()->view('NsMultiStore::frontend.not-allowed-store-access', [
                'title'         =>  __('Not Allowed Store Access'),
                'message'       =>  $this->getMessage() ?: __('You\'re not allowed to see that page.'),
            ]);
        }

        return response()->json([
            'status'  =>  'error',
            'message' => $this->getMessage() ?: __('You\'re not allowed to see that page.'),
        ], 401);
    }
}
