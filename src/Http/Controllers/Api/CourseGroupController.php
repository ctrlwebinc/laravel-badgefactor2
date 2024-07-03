<?php

namespace Ctrlweb\BadgeFactor2\Http\Controllers\Api;

use Ctrlweb\BadgeFactor2\Http\Controllers\Controller;
use Ctrlweb\BadgeFactor2\Http\Resources\Badges\BadgePageResource;
use Ctrlweb\BadgeFactor2\Http\Resources\Courses\CourseGroupResource;
use Ctrlweb\BadgeFactor2\Models\Badges\BadgePage;
use Ctrlweb\BadgeFactor2\Models\Courses\CourseGroup;
use Illuminate\Http\Request;

/**
 * @tags Groupes de cours (formations)
 */
class CourseGroupController extends Controller
{
    /**
     * Liste des groupes de cours.
     *
     * Il est possible de filtrer les formations par catégorie de cours et par mot-clé.
     *
     * @param Request $request
     *
     * @return void
     */
    public function index(string $locale, Request $request)
    {
        $request->validate([
            'course_group_category' => 'nullable|integer',
            'q'                     => 'nullable|string',
            'issuer'                => 'nullable|string',
            'badge_category'        => 'string',
        ]);

        $badgeCategory = request()->input('badge_category');

        if (!empty($badgeCategory) && $badgeCategory !== 'certification') {
            $query = BadgePage::query();
            $groups = $query->orderByDesc('updated_at')->paginate(12);

            return BadgePageResource::collection($groups);
        } else {
            $query = CourseGroup::query();
            $groups = $query->orderByDesc('updated_at')->paginate(12);

            return CourseGroupResource::collection($groups);
        }
    }

    public function show(string $locale, $slug)
    {
        return CourseGroupResource::make($slug);
    }
}
