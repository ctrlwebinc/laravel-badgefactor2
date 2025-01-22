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

            return $this->getIssuerByBadgeCategories([$badgeCategory->id]);
        }

        return response()->json([]);

    }

    public function issuerWithoutCertification(){
        $certificationBadgeCategory = BadgeCategory::findBySlug('certification')->first();

        $badgeCategories = BadgeCategory::where('id', '!=', $certificationBadgeCategory->id)->pluck('id');
        
        if( !empty($badgeCategories)){

            return $this->getIssuerByBadgeCategories($badgeCategories->toArray(), true);
        }

        return response()->json([]);

    }

    private function getIssuerByBadgeCategories(Array $badgeCategoryIds, bool $withNull = false) {
        
        if(!empty($badgeCategoryIds)){
            
            $badges = BadgePage::with('badge')
                ->where(function($query) use ($badgeCategoryIds, $withNull){
                    return $query->whereIn("badge_category_id", $badgeCategoryIds)
                                    ->when($withNull, function($q){
                                        return $q->orWhereNull('badge_category_id');
                                    });
                })                  
                ->when(!$withNull, function($q){
                    return $q->whereHas('course', function($q){
                        return $q->whereNotNull('course_group_id');
                    });
                })
                
                ->isPublished()->where('is_hidden', false)                                  
                ->get();
            
            $issuers = $badges->map(function($badgePage){
                $issuer = null ;

                try {
    
                    $badge = $badgePage->badge;
                    $issuer = $badge->issuer;
    
                } catch (\Throwable $th) {}
    
                return $issuer;
            })->filter(function($issuer){
                return $issuer; 
            })->unique('entityId') ?? [];

            return $issuers;
        }

        return [];

    }
}
