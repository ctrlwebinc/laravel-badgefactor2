<?php

namespace Ctrlweb\BadgeFactor2\Http\Controllers\Api\Badgr;

use Ctrlweb\BadgeFactor2\Services\Badgr\Issuer;
use Ctrlweb\BadgeFactor2\Models\Badges\BadgePage;
use Ctrlweb\BadgeFactor2\Http\Controllers\Controller;
use Ctrlweb\BadgeFactor2\Models\Badges\BadgeCategory;

/**
 * @tags Emetteurs
 */
class IssuerController extends Controller
{
    public function index()
    {
        $issuers = app(Issuer::class)->all();

        return response()->json($issuers);
    }

    public function show(string $locale, string $entityId)
    {
        $issuer = app(Issuer::class)->getBySlug($entityId);

        return response()->json($issuer);
    }

    public function count()
    {
        return app(Issuer::class)->count();
    }

    public function issuerWithCertification(){
        $badgeCategory = BadgeCategory::findBySlug('certification')->first();
        
        if($badgeCategory){

            $badges = BadgePage::with('badge')
                ->where("badge_category_id", $badgeCategory->id)                                    
                ->get();
            
            $issuers = $badges->map(function($badgePage){
                $issuer = null ;

                try {
    
                    $badge = $badgePage->badge;
                    $issuer = $badge->issuer;
    
                } catch (\Throwable $th) {
                    //throw $th;
                }
    
                return $issuer;
            })->filter(function($issuer){
                return $issuer; 
            })->unique('entityId') ?? [];

            return response()->json($issuers);
        }

        return response()->json([]);

    }
}
