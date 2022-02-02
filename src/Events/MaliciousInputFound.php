<?php

namespace ProtoneMedia\LaravelXssProtection\Events;

use Illuminate\Http\Request;

class MaliciousInputFound
{
    public function __construct(public array $keys, public Request $request)
    {
    }
}
