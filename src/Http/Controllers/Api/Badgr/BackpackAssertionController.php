<?php

namespace Ctrlweb\BadgeFactor2\Http\Controllers\Api\Badgr;

use Ctrlweb\BadgeFactor2\Http\Controllers\Controller;
use Ctrlweb\BadgeFactor2\Models\Badgr\Assertion;
use Ctrlweb\BadgeFactor2\Models\User;

class BackpackAssertionController extends Controller
{
    public function index($locale, $learner)
    {
        User::where('slug', $learner)->firstOrFail();

        $assertions = Assertion::with(['issuer', 'badgeclass'])->get();

        if (!$assertions) {
            return response()->json([], 404);
        }

        return response()->json($assertions);
    }
}
