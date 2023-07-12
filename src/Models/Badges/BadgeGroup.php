<?php

namespace Ctrlweb\BadgeFactor2\Models\Badges;

use Ctrlweb\BadgeFactor2\Models\BadgeGroup as BadgeFactor2BadgeGroup;
use Spatie\Translatable\HasTranslations;

class BadgeGroup extends BadgeFactor2BadgeGroup
{
    use HasTranslations;

    public static function findBySlug($slug)
    {
        return self::where('slug->fr', $slug)
            ->orWhere('slug->en', $slug);
    }

    protected $translatable = [
        'title',
        'slug',
        'description',
    ];
}
