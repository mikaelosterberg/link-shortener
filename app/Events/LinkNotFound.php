<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Http\Request;

class LinkNotFound
{
    use Dispatchable;

    public function __construct(
        public readonly string $shortCode,
        public readonly Request $request
    ) {}
}
