<?php

namespace Ctrlweb\BadgeFactor2\Http\Controllers\Api\Badgr;

use Carbon\Carbon;
use Ctrlweb\BadgeFactor2\Helpers\LinkedIn;
use Ctrlweb\BadgeFactor2\Http\Controllers\Controller;
use Ctrlweb\BadgeFactor2\Models\Badgr\Assertion;
use Ctrlweb\BadgeFactor2\Models\Badgr\Badge;
use Ctrlweb\BadgeFactor2\Models\User;

/**
 * @tags Assertions
 */
class AssertionController extends Controller
{
    public function index()
    {
        $assertions = Assertion::all();

        if (!$assertions) {
            return response()->json([], 404);
        }

        return response()->json($assertions);
    }

    public function show(string $locale, string $entityId)
    {
        $assertion = app(Assertion::class)->where('entityId', $entityId)->get();

        return response()->json($assertion);
    }

    public function shareToLinkedIn(string $locale, string $entityId)
    {
        $assertion = app(Assertion::class)->where('entityId', $entityId)->first();
        $badge = app(Badge::class)->where('entityId', $assertion->badgeclass_id)->first();
        $issueDate = Carbon::parse($assertion->issuedOn);
        $user = User::where('email', $assertion->recipient_email)->first();

        $assertionUrl = config('badgefactor2.frontend.url').'/membres/'.$user->slug.'/badges/'.$badge->slug;

        $fbLink = LinkedIn::generateLink($badge->name, $issueDate->year, $issueDate->month, $assertionUrl, $assertion->entityId);

        return response()->json(['link' => $fbLink]);
    }
}
