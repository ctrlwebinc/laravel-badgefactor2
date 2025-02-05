<?php

namespace Ctrlweb\BadgeFactor2\Models\Badges;

use Ctrlweb\BadgeFactor2\Models\BadgeCategory as BadgeFactor2BadgeCategory;
use Spatie\Translatable\HasTranslations;
use Illuminate\Support\Facades\Cache;


class BadgeCategory extends BadgeFactor2BadgeCategory
{
    use HasTranslations;

    public static function findBySlug($slug)
    {
        return self::where('slug->fr', $slug)
            ->orWhere('slug->en', $slug);
    }

    protected $translatable = [
        'title',
        'subtitle',
        'slug',
        'description',
    ];

    public static function boot()
    {
        parent::boot();        

        $caches = ['badge_category_certification_*', 'badge_categories'];

        foreach ($caches as $key => $cache) {

            static::saved(function () {
                Cache::forget($cache);
            });
    
            static::updated(function () {
                Cache::forget($cache);
            });
        
            static::deleted(function () {
                Cache::forget($cache);
            });
        }
        
    }
}
