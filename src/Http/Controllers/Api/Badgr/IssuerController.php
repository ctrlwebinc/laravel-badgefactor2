<?php

namespace Ctrlweb\BadgeFactor2\Http\Controllers\Api\Badgr;

use Ctrlweb\BadgeFactor2\Models\Badges\BadgePage;
use Ctrlweb\BadgeFactor2\Models\Courses\CourseCategory;
use Ctrlweb\BadgeFactor2\Http\Controllers\Controller;
use Ctrlweb\BadgeFactor2\Http\Resources\Badgr\IssuerResource;
use Ctrlweb\BadgeFactor2\Services\Badgr\Badge;
use Ctrlweb\BadgeFactor2\Services\Badgr\Issuer;

/**
 * @tags Emetteurs
 */
class IssuerController extends Controller
{
    public function index()
    {
        $issuers = app(Issuer::class)->all();
        if (request('course-category')) {
            $filteredBadgePages = CourseCategory::findBySlug(request('course-category'))->first()->courses->pluck('badge_id');
            $filteredBadges = BadgePage::whereIn('id', $filteredBadgePages)->pluck('badgeclass_id');
            $filteredIssuers = collect(app(Badge::class)->all())->whereIn('entityId', $filteredBadges)->pluck('issuer');
            $issuers = collect($issuers);
            $issuers = $issuers->whereIn('entityId', $filteredIssuers)->toArray();
        }

        return response()->json($issuers);
    }

    public function show(string $locale, string $entityId)
    {
        $issuer = app(Issuer::class)->getBySlug($entityId);
        return response()->json($issuer);
    }

    public function count()
    {
        return app(Issuer::class)->count();
    }
}
