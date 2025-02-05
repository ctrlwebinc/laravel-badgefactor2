<?php

namespace Ctrlweb\BadgeFactor2\Http\Controllers\Api;

use Ctrlweb\BadgeFactor2\Http\Controllers\Controller;
use Ctrlweb\BadgeFactor2\Http\Resources\Courses\BasicCourseGroupCategoryResource;
use Ctrlweb\BadgeFactor2\Http\Resources\Courses\CourseGroupCategoryResource;
use Ctrlweb\BadgeFactor2\Models\Courses\CourseGroupCategory;
use App\Helpers\CacheHelper;

/**
 * @tags Catégories de groupes de cours
 */
class CourseGroupCategoryController extends Controller
{
    public function index()
    {
        $categories = CacheHelper::rememberForeverWithGroup('course_group_category', 'course_group_category_list' . md5(request()->fullUrl()), function() {
            return CourseGroupCategory::when(request()->input('is_active'), function($query){
                return $query->whereHas('courseGroups');
            })->paginate();
        });
        

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
