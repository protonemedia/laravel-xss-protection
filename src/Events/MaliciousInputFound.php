<?php

namespace ProtoneMedia\LaravelXssProtection\Events;

use Illuminate\Http\Request;

class MaliciousInputFound
{
    public function __construct(
        public array $sanitizedKeys,
        public Request $originalRequest,
        public Request $sanitizedRequest
    )
    {
    }
}
