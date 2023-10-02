<?php

namespace Ctrlweb\BadgeFactor2\Http\Controllers\Api\Badgr;

use Ctrlweb\BadgeFactor2\Http\Controllers\Controller;
use Ctrlweb\BadgeFactor2\Models\Badgr\Assertion;
use Ctrlweb\BadgeFactor2\Models\User;
use Ctrlweb\BadgeFactor2\Http\Controllers\Api\Badgr\BackpackAssertionCollection;

class BackpackAssertionController extends Controller
{
    public function index($locale,$learner)
    {
        User::where('slug',$learner)->firstOrFail();
        
        $assertions = Assertion::all();

        if (!$assertions) {
            return response()->json([], 404);
        }

        return response()->json($assertions);    }
}
