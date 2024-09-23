<?php

namespace Ctrlweb\BadgeFactor2\Http\Controllers\Api;

use App\Helpers\ECommerceHelper;
use Ctrlweb\BadgeFactor2\Events\CourseAccessed;
use Ctrlweb\BadgeFactor2\Http\Controllers\Controller;
use Ctrlweb\BadgeFactor2\Models\Badges\BadgePage;

/**
 * @tags Catégories de groupes de cours
 */
class CourseController extends Controller
{
    public function validateAccess(string $locale, string $slug)
    {
        $course = BadgePage::where('slug->fr', $slug)->firstOrFail()->course;
        $currentUser = auth()->user();

        $allowedEmails = ["aurelie.leclerc@ac-amiens.fr", "vincent.marchand1@ac-amiens.fr", "emilie.arculeo@gmail.com"];

        if ($course && $currentUser->freeAccess || ECommerceHelper::hasAccess($currentUser, $course) || (in_array($currentUser->email, $allowedEmails) && $course->course_group_id == 126)) {
            CourseAccessed::dispatch($currentUser, $course);

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
