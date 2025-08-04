<?php

namespace Ctrlweb\BadgeFactor2\Http\Controllers\Api;

use Ctrlweb\BadgeFactor2\Events\CourseAccessed;
use Ctrlweb\BadgeFactor2\Http\Controllers\Controller;
use Ctrlweb\BadgeFactor2\Models\Badges\BadgePage;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use App\Helpers\ECommerceHelper;

/**
 * @tags Catégories de groupes de cours
 */
class CourseController extends Controller
{
    public function validateAccess(string $locale, string $slug, Request $request)
    {
        $course = BadgePage::where('slug->fr', $slug)->firstOrFail()->course;
        $bearerToken = Str::remove('Bearer ', $request->header('Authorization'));
        $sessionToken = substr($bearerToken, strpos($bearerToken, '|') + 1);
        Session::setId($sessionToken);
        Session::start();
        $currentUser = auth()->user();

        $allowedEmails = ["aurelie.leclerc@ac-amiens.fr", "vincent.marchand1@ac-amiens.fr", "emilie.arculeo@gmail.com"];

        if ($course && $currentUser->freeAccess || ECommerceHelper::hasAccess($currentUser, $course) || (in_array(strtolower($currentUser->email), $allowedEmails) && $course->courseGroup?->slug == "conception-universelle-de-lapprentissage")) {
            CourseAccessed::dispatch($currentUser, $course);

            $courseOrder = ECommerceHelper::getSucessfulCourseOrder($currentUser, $course);
            $item = $courseOrder->items()->where('product_type', \App\Models\Course::class)
                        ->where('product_id', $course->id)->first();

            return response()->json([
                'access' => true,
                'expires_at' => $item->expires_at
                
            ]);
        } else {
            return response()->json([
                'access'   => false,
                'redirect' => config('badgefactor2.frontend.url').'/badges/'.$course->badgePage->slug,
            ], 302);
        }
    }
    
}
