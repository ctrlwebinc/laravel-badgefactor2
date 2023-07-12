<?php

namespace Ctrlweb\BadgeFactor2\Http\Controllers\Api;

use Ctrlweb\BadgeFactor2\Http\Controllers\Controller;
use Ctrlweb\BadgeFactor2\Http\Resources\Badges\BadgeCategoryResource;
use Ctrlweb\BadgeFactor2\Models\Badges\BadgeCategory;

class BadgeCategoryController extends Controller
{
    public function index()
    {
        $categories = BadgeCategory::paginate();

        return BadgeCategoryResource::collection($categories);
    }

    public function show(string $locale, $slug)
    {
        return BadgeCategoryResource::make($slug);
    }
}
