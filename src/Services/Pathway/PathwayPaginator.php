<?php

namespace Ctrlweb\BadgeFactor2\Services\Pathway;

use App\Models\PathwayPage;

class PathwayPaginator 
{
    public static function queryPathWays(String $witch = 'is_badgepage', String $search = null){

        $pathwayQuery = PathwayPage::where($witch, true)
                        ->when($search, function($query) use ($search){
                            
                            return $query->where(function($q) use ($search) {

                                $path = '$."'.app()->getLocale().'"';
                                $like = "%{$search}%";

                                return $q->whereRaw(
                                        "LOWER(CONVERT(JSON_EXTRACT(title, ?) USING utf8mb4)) LIKE ?",
                                        [$path, $like]
                                    )->orWhereRaw(
                                        "LOWER(CONVERT(JSON_EXTRACT(slug, ?) USING utf8mb4)) LIKE ?",
                                        [$path, $like]
                                    )->orWhereRaw(
                                        "LOWER(CONVERT(JSON_EXTRACT(content, ?) USING utf8mb4)) LIKE ?",
                                        [$path, $like]
                                    );
                            });
                           
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
