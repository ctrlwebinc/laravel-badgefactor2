<?php

namespace Ctrlweb\BadgeFactor2\Http\Controllers\Api;


use Ctrlweb\BadgeFactor2\Models\Badges\BadgePage;
use Ctrlweb\BadgeFactor2\Models\Courses\Course;
use Ctrlweb\BadgeFactor2\Http\Controllers\Controller;
use Ctrlweb\BadgeFactor2\Http\Resources\Courses\CourseResource;

/**
 * @tags Catégories de groupes de cours
 */
class CourseController extends Controller
{
    public function validateAccess(string $locale, $course)
    {
        $hasAccess = false;
        $badgePage = BadgePage::where('slug->fr', $course)->first();
        $currentUser = auth()->user();
        if ($currentUser && $badgePage) {
            if ($currentUser->free_access) {
                $hasAccess = true;
            } else {
                // TODO Check if course purchased.
            }
        }

        if ($hasAccess) {
            return response()->json([
                'access' => $hasAccess,
            ]);
        } else {
            return response()->json([
                'access' => $hasAccess,
                'redirect' => config('badgefactor2.frontend.url').'/badges/'.$badgePage->slug,
            ], 302);
        }

    }
}
