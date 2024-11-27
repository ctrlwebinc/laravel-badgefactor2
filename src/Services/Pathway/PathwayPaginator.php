<?php

namespace Ctrlweb\BadgeFactor2\Services\Pathway;

use Ctrlweb\BadgeFactor2\Models\PathwayPage;

class PathwayPaginator 
{
    public static function queryPathWays(String $witch = 'is_badgepage', String $search = null){

        $pathwayQuery = PathwayPage::where($witch, true)
                        ->when($search, function($query) use ($search){

                            $locale = app()->getLocale();

                            return $query->whereRaw(
                                    "LOWER(
                                    CONVERT(title->'$.$locale' USING utf8mb4)) LIKE ?",
                                    "%{$search}%"
                                )->orWhereRaw(
                                    "LOWER(
                                    CONVERT(slug->'$.$locale' USING utf8mb4)) LIKE ?",
                                    "%{$search}%"
                                )->orWhereRaw(
                                    "LOWER(
                                    CONVERT(content->'$.$locale' USING utf8mb4)) LIKE ?",
                                    "%{$search}%"
                                );
                           
                        });

        return  $pathwayQuery;
    }

    public static function getBridge($paginatedCollection){        
        return $paginatedCollection->resource?->perPage() 
            - (($paginatedCollection->resource?->perPage() * ( $paginatedCollection->resource?->lastPage() ?? 0 )) 
            - $paginatedCollection->resource?->total());

    }

    public static function getOffset($currenPage, $perPage, $bridge){
        return ($currenPage * $perPage) - $bridge;
    }
    
}
