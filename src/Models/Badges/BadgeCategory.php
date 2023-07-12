<?php

namespace Ctrlweb\BadgeFactor2\Models\Badges;

use Ctrlweb\BadgeFactor2\Models\BadgeCategory as BadgeFactor2BadgeCategory;
use Spatie\Translatable\HasTranslations;

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
}
