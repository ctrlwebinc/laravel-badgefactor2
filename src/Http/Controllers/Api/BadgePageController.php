<?php

namespace Ctrlweb\BadgeFactor2\Http\Controllers\Api;

use Illuminate\Http\Request;
use Ctrlweb\BadgeFactor2\Models\Badges\BadgePage;
use Ctrlweb\BadgeFactor2\Services\Badgr\Assertion;
use Ctrlweb\BadgeFactor2\Http\Controllers\Controller;
use Ctrlweb\BadgeFactor2\Models\Badges\BadgeCategory;
use Ctrlweb\BadgeFactor2\Http\Resources\Badgr\AssertionResource;
use Ctrlweb\BadgeFactor2\Http\Resources\Badges\BadgePageResource;
use Ctrlweb\BadgeFactor2\Http\Resources\Badges\BadgeCategoryResource;
use Illuminate\Support\Facades\Cache;


/**
 * @tags Badges
 */
class BadgePageController extends Controller
{
    /**
     * Liste des badges.
     *
     * Les valeurs de `badge_category` sont :
     * - `reconnaissance` correspond à l'onglet "Badge événementiels"
     * - `badges-lecture` correspond à l'onglet "Badges de lecture professionnelle"
     *
     * Dans le cas de `certification`, il est possible de filtrer par `course_group` (ID du groupe de cours)
     * et par `q` (recherche dans le titre, le slug et la description).
     *
     * Dans le cas de `reconnaissance` et `badges-lecture`, il est possible de filtrer par `issuer`
     * (ID de [l'émetteur](/paths/locale--issuers/get))
     * et par `q` (recherche dans le titre, le slug et la description).
     *
     * @param Request $request
     *
     * @return void
     */
    public function index(Request $request)
    {
        $request->validate([
            'badge_category' => 'in:certification,reconnaissance,badges-lecture',
            'course_group'   => 'nullable|exists:course_groups,id',
            'issuer'         => 'nullable',
            'q'              => 'nullable',
        ]);

        return BadgePageResource::collection(BadgePage::paginate());
    }

    public function show(string $locale, $badge)
    {
        return BadgePageResource::make($badge);
    }

    public function showIssued(string $locale, $slug)
    {
        $badgePage = BadgePage::where("slug->{$locale}", '=', $slug)->first();
        $assertions = app(Assertion::class)->getByBadgeClass($badgePage->badgeclass_id);

        return AssertionResource::collection($assertions);
    }

    public function badgePageByCourseGroup(string $locale, $courseGroup)
    {
        $badgePages = Cache::rememberForever('badge_pages_' . $courseGroup->id, function() use ($courseGroup) {
            return BadgePage::whereHas('course', function ($query) use ($courseGroup) {
                $query->where('course_group_id', $courseGroup->id);
            })->isPublished()->get();
        });
        

        return BadgePageResource::collection($badgePages);
    }

    public function badgePageCategory()
    {
        $categories = Cache::rememberForever('badge_categories', function() {
            return BadgeCategory::all();
        });
        
        return BadgeCategoryResource::collection($categories);
    }
}
