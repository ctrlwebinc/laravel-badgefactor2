<?php

namespace Ctrlweb\BadgeFactor2\Http\Controllers\Api;

use Ctrlweb\BadgeFactor2\Http\Controllers\Controller;
use Ctrlweb\BadgeFactor2\Models\Badges\BadgePage;
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
            $search['badges'] = BadgePage::search($string)->get();
        } catch (ApiException $e) {
            BadgePage::all()->searchable();
        }

        try {
            $search['courses'] = Course::search($string)->get();
        } catch (ApiException $e) {
            Course::all()->searchable();
        }

        try {
            $search['course_groups'] = CourseGroup::search($string)->get();
        } catch (ApiException $e) {
            CourseGroup::all()->searchable();
        }

        try {
            $search['course_group_categories'] = CourseGroupCategory::search($string)->get();
        } catch (ApiException $e) {
            CourseGroupCategory::all()->searchable();
        }

        return $search;
    }
}
