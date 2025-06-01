<?php

namespace App\Events;

use App\Models\Link;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Http\Request;

class LinkClicked
{
    use Dispatchable;

    public function __construct(
        public readonly Link $link,
        public readonly Request $request
    ) {}
}