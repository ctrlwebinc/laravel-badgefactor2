<?php

namespace Ctrlweb\BadgeFactor2\Models\Courses;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class CourseGroupCategory extends Model
{
    use HasTranslations;

    protected $fillable = [
        'slug',
        'title',
        'excerpt',
        'subtitle',
        'image',
        'description',
        'menu_title',
        'is_featured'
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
        'is_featured' => 'boolean'
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
}
