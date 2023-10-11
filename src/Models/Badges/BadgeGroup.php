<?php

namespace Ctrlweb\BadgeFactor2\Models\Badges;

use Ctrlweb\BadgeFactor2\Models\BadgeGroup as BadgeFactor2BadgeGroup;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Translatable\HasTranslations;

class BadgeGroup extends BadgeFactor2BadgeGroup implements HasMedia
{
    use HasTranslations;
    use InteractsWithMedia;

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

    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(32)
            ->height(32);
    }
}
