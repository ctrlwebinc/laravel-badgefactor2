<?php

namespace Ctrlweb\BadgeFactor2\Models\Courses;

use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Translatable\HasTranslations;
use App\Helpers\CacheHelper;

class CourseGroupCategory extends Model implements HasMedia
{
    use HasTranslations;
    use InteractsWithMedia;
    use Searchable;

    protected $fillable = [
        'slug',
        'title',
        'subtitle',
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

    public static function boot()
    {
        parent::boot();

        $caches = ['course_group_category', 'search_engine_response'];        

        foreach ($caches as $key => $cache) {

            static::saved(function () use ($cache) {
                CacheHelper::forgetGroup($cache);
            });
    
            static::updated(function () use ($cache) {
                CacheHelper::forgetGroup($cache);
            });
        
            static::deleted(function () use ($cache) {
                CacheHelper::forgetGroup($cache);
            });
        }

        
    }

    public static function findBySlug($slug)
    {
        return self::where('slug->fr', $slug)
            ->orWhere('slug->en', $slug);
    }

    public function courseGroups()
    {
        return $this->hasMany(CourseGroup::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('image')->singleFile();
    }

    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(32)
            ->height(32);
    }
}
