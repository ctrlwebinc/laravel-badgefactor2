<?php

namespace Ctrlweb\BadgeFactor2\Http\Controllers\Api;

use Ctrlweb\BadgeFactor2\Http\Controllers\Controller;
use Ctrlweb\BadgeFactor2\Models\Courses\Course;
use Ctrlweb\BadgeFactor2\Models\Courses\CourseGroup;
use Ctrlweb\BadgeFactor2\Models\Courses\CourseGroupCategory;
use Illuminate\Http\Request;
use Meilisearch\Exceptions\ApiException;

class SearchController extends Controller
{
    public function __invoke(string $locale, Request $request, string $string)
    {
        $search = [];

        try {
            $search['courses'] = Course::search($string)->get();
        } catch (ApiException $e) {
            Course::all()->searchable();
            $search['courses'] = Course::search($string)->get();
        }

        try {
            $search['course_groups'] = CourseGroup::search($string)->get();
        } catch (ApiException $e) {
            CourseGroup::all()->searchable();
            $search['course_groups'] = CourseGroup::search($string)->get();
        }

        try {
            $search['course_group_categories'] = CourseGroupCategory::search($string)->get();
        } catch (ApiException $e) {
            CourseGroupCategory::all()->searchable();
            $search['course_group_categories'] = CourseGroupCategory::search($string)->get();
        }

        return $search;
    }
}
