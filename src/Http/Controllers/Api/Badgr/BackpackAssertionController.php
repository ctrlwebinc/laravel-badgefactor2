<?php

namespace Ctrlweb\BadgeFactor2\Http\Controllers\Api\Badgr;

use App\Http\Requests\AssertionVisibilityRequest;
use Ctrlweb\BadgeFactor2\Http\Controllers\Controller;
use Ctrlweb\BadgeFactor2\Http\Resources\Badgr\AssertionUserResource;
use Ctrlweb\BadgeFactor2\Models\Badgr\Assertion;
use Ctrlweb\BadgeFactor2\Models\Badgr\AssertionUser;
use Ctrlweb\BadgeFactor2\Models\User;

class BackpackAssertionController extends Controller
{
    public function index($locale, $learner)
    {
        $user = User::where('slug', $learner)->firstOrFail();
        $assertionsVisibility = AssertionUser::where('user_id', '=', $user->id)->get()->keyBy('assertion_id');

        //dd($assertionsVisibility);
        $assertions = Assertion::with(['issuer', 'badgeclass'])->get();

        if (!$assertions) {
            return response()->json([], 404);
        }

        foreach ($assertions as $assertion) {
            $assertion->is_visible = $assertionsVisibility->get($assertion->entityId) ? $assertionsVisibility->get($assertion->entityId)->is_visible : true;
        }

        return response()->json($assertions);
    }

    public function toggleVisibility(AssertionVisibilityRequest $request, string $entityId)
    {
        $validated = $request->validated();

        $assertionVisibility = AssertionUser::updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'assertion_id' => $entityId,
            ],
            [
                'is_visible' => $request->input('is_visible')
            ]
        );

        if (!$assertionVisibility) {
            return response()->json([
                'success' => false,
                'message' => __('assertion.visibility.failed'),
            ]);
        }

        return response()->json([
            'success' => true,
            'assertion' => AssertionUserResource::make($assertionVisibility),
        ]);
    }
}
