<?php

namespace Ctrlweb\BadgeFactor2\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Translatable\HasTranslations;
use Spatie\MediaLibrary\MediaCollections\Models\Media;


class PathwayPage extends Model implements HasMedia
{
    use HasFactory, HasTranslations, InteractsWithMedia;

    protected $translatable = [
        'title',
        'slug',
        'description',
        'content',
        'criteria',
    ];

    protected $fillable = [
        'title',
        'description',
        'content',
        'duration',
        'criteria',
        'technical_requirements',
        'target_audience',
        'is_autoformation',
        'is_badgepage'
    ];

    public function registerMediaConversions(Media $media = null): void {
        $this->addMediaConversion('thumb')
            ->width(32)
            ->height(32);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('pathwayImages')->singleFile();
    }

}
