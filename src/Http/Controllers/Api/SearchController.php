<?php

namespace Ctrlweb\BadgeFactor2\Http\Controllers\Api;

use Ctrlweb\BadgeFactor2\Http\Controllers\Controller;
use Ctrlweb\BadgeFactor2\Models\Courses\Course;
use Ctrlweb\BadgeFactor2\Models\Courses\CourseGroup;
use Ctrlweb\BadgeFactor2\Models\Courses\CourseGroupCategory;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __invoke(string $locale, Request $request, string $string)
    {
        $search = [];
        $search['courses'] = Course::search($string)->get();
        $search['course_groups'] = CourseGroup::search($string)->get();
        $search['course_group_categories'] = CourseGroupCategory::search($string)->get();

        return $search;
    }
}
