<?php

namespace Ctrlweb\BadgeFactor2\Http\Controllers\Api;

use Ctrlweb\BadgeFactor2\Http\Controllers\Controller;
use Ctrlweb\BadgeFactor2\Http\Resources\Badges\BadgeGroupResource;
use Ctrlweb\BadgeFactor2\Models\Badges\BadgeGroup;

class BadgeGroupController extends Controller
{
    public function index()
    {
        $groups = BadgeGroup::paginate();

        return BadgeGroupResource::collection($groups);
    }

    public function show(string $locale, $slug)
    {
        return BadgeGroupResource::make($slug);
    }
}
