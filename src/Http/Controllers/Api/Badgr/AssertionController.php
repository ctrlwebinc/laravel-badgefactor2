<?php

namespace Ctrlweb\BadgeFactor2\Http\Controllers\Api\Badgr;

use Ctrlweb\BadgeFactor2\Http\Controllers\Controller;
use Ctrlweb\BadgeFactor2\Models\Badgr\Assertion;

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
}
