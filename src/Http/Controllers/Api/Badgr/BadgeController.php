<?php

namespace Ctrlweb\BadgeFactor2\Http\Controllers\Api\Badgr;

use Ctrlweb\BadgeFactor2\Http\Controllers\Controller;
use Ctrlweb\BadgeFactor2\Services\Badgr\Badge;

/**
 * @tags Emetteurs
 */
class BadgeController extends Controller
{
    public function index()
    {
        $badges = app(Badge::class)->all();

        return response()->json($badges);
    }

    public function show(string $locale, string $entityId)
    {
        $badge = app(Badge::class)->getBySlug($entityId);

        return response()->json($badge);
    }

    public function count()
    {
        return app(Badge::class)->count();
    }
}
