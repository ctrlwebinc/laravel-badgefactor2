<?php

namespace Ctrlweb\BadgeFactor2\Http\Controllers\Api;

use Ctrlweb\BadgeFactor2\Http\Controllers\Controller;
use Ctrlweb\BadgeFactor2\Http\Resources\Courses\BasicCourseGroupCategoryResource;
use Ctrlweb\BadgeFactor2\Http\Resources\Courses\CourseGroupCategoryResource;
use Ctrlweb\BadgeFactor2\Models\Courses\CourseGroupCategory;

/**
 * @tags Catégories de groupes de cours
 */
class CourseGroupCategoryController extends Controller
{
    public function index()
    {

        $categories = CourseGroupCategory::paginate();

        return CourseGroupCategoryResource::collection($categories);
    }

    public function show(string $locale, $courseGroupCategory)
    {
        return CourseGroupCategoryResource::make($courseGroupCategory);
    }

    public function featured(string $locale)
    {
        $categories = CourseGroupCategory::where('is_featured', true)->orderBy('title')->take(5)->get();

        return BasicCourseGroupCategoryResource::collection($categories);
    }
}
