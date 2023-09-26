<?php

namespace Ctrlweb\BadgeFactor2\Models\Courses;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Translatable\HasTranslations;

class CourseCategory extends Model implements HasMedia
{
    use HasTranslations;
    use InteractsWithMedia;

    public static function findBySlug($slug)
    {
        return self::where('slug->fr', $slug)
            ->orWhere('slug->en', $slug);
    }

    protected $fillable = [
        'title',
        'slug',
        'description',
        'image',
        'parent_id',
    ];

    protected $translatable = [
        'title',
        'subtitle',
        'slug',
        'description',
    ];

    protected $with = [
        'courses',
    ];

    public function courses()
    {
        return $this->hasMany(Course::class);
    }
}
