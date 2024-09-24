<?php

namespace Ctrlweb\BadgeFactor2\Http\Controllers\Api\Badgr;

use App\Helpers\ECommerceHelper;
use Ctrlweb\BadgeFactor2\Events\BadgeRequestFormAccessed;
use Ctrlweb\BadgeFactor2\Http\Controllers\Controller;
use Ctrlweb\BadgeFactor2\Models\Badges\BadgePage;
use Ctrlweb\BadgeFactor2\Services\Badgr\Badge;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

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

    public function validateAccess(string $locale, string $entityId)
    {
        $bearerToken = Str::remove('Bearer ', $request->header('Authorization'));
        $sessionToken = substr($bearerToken, strpos($bearerToken, '|') + 1);
        Session::setId($sessionToken);
        Session::start();

        $badgePage = BadgePage::where('badgeclass_id', '=', $entityId)->first();
        if (!$badgePage) {
            return response()->json([
                'access'   => false,
                'redirect' => config('badgefactor2.frontend.url'),
            ], 302);
        }
        $course = $badgePage->course;
        $currentUser = auth()->user();

        if ($course && $currentUser->freeAccess || ECommerceHelper::hasAccess($currentUser, $course)) {
            BadgeRequestFormAccessed::dispatch($currentUser, $badgePage->badge);

            return response()->json([
                'access' => true,
            ]);
        } else {
            return response()->json([
                'access'   => false,
                'redirect' => config('badgefactor2.frontend.url').'/badges/'.$badgePage->slug,
            ], 302);
        }
    }
}
