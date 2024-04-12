<?php

namespace Ctrlweb\BadgeFactor2\Http\Controllers\Api;

use App\Helpers\ECommerceHelper;
use Ctrlweb\BadgeFactor2\Http\Controllers\Controller;
use Ctrlweb\BadgeFactor2\Models\Badges\BadgePage;
use Ctrlweb\BadgeFactor2\Models\Courses\Course;

/**
 * @tags Catégories de groupes de cours
 */
class CourseController extends Controller
{
    public function validateAccess(string $locale, string $slug)
    {
        $course = Course::where('slug->fr', $slug)->firstOrFail();
        $currentUser = auth()->user();

        if ($currentUser->freeAccess || ECommerceHelper::hasAccess($currentUser, $course)) {
            return response()->json([
                'access' => true,
            ]);
        } else {
            return response()->json([
                'access'   => false,
                'redirect' => config('badgefactor2.frontend.url').'/badges/'.$course->badgePage->slug,
            ], 302);
        }
    }
}
