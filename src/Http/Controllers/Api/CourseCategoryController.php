<?php

namespace Ctrlweb\BadgeFactor2\Http\Controllers\Api;

use Ctrlweb\BadgeFactor2\Http\Controllers\Controller;
use Ctrlweb\BadgeFactor2\Http\Resources\Courses\CourseCategoryResource;
use Ctrlweb\BadgeFactor2\Models\Courses\CourseCategory;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * @tags Catégories de cours
 */
class CourseCategoryController extends Controller
{
    /**
     * @return AnonymousResourceCollection<LengthAwarePaginator<CourseCategoryResource>>
     */
    public function index()
    {
        $categories = CourseCategory::paginate();

        return CourseCategoryResource::collection($categories);
    }

    public function show(string $locale, $slug)
    {
        return CourseCategoryResource::make($slug);
    }
}
