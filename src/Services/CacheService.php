<?php

namespace Ctrlweb\BadgeFactor2\Services;
use Illuminate\Support\Facades\Cache;


class CacheService 
{
    public static function restoreCache($model, Array $caches){

        foreach ($caches as $key => $cache) {

            $model::saved(function () use ($cache) {
                Cache::forget($cache);
            });
    
            $model::updated(function () use ($cache) {
                Cache::forget($cache);
            });
        
            $model::deleted(function () use ($cache) {
                Cache::forget($cache);
            });
        }        

    }    
    
}
