<?php

namespace Ctrlweb\BadgeFactor2\Models\Courses;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class CourseCategory extends Model
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

    protected $with = [
        'courses',
    ];

    public function courses()
    {
        return $this->hasMany(Course::class);
    }
}
