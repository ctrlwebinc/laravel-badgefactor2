<?php

namespace Ctrlweb\BadgeFactor2\Models\Courses;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Translatable\HasTranslations;

class CourseGroupCategory extends Model implements HasMedia
{
    use HasTranslations;
    use InteractsWithMedia;

    protected $fillable = [
        'slug',
        'title',
        'subtitle',
        'image',
        'description',
        'menu_title',
        'is_featured',
    ];

    protected $translatable = [
        'slug',
        'title',
        'excerpt',
        'subtitle',
        'description',
        'menu_title',
    ];

    protected $casts = [
        'is_featured' => 'boolean',
    ];

    public static function findBySlug($slug)
    {
        return self::where('slug->fr', $slug)
            ->orWhere('slug->en', $slug);
    }

    public function courseGroups()
    {
        return $this->hasMany(CourseGroup::class);
    }

    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(32)
            ->height(32);
    }
}
