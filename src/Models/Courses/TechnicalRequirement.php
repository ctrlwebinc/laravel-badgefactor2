<?php

namespace Ctrlweb\BadgeFactor2\Models\Courses;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class TechnicalRequirement extends Model
{
    use HasFactory;
    use HasTranslations;

    protected $fillable = [
        'title',
        'slug',
        'description',
        'image',
    ];

    protected $translatable = [
        'title',
        'slug',
        'description',
    ];

    public static function findBySlug($slug)
    {
        return self::where('slug->fr', $slug)
            ->orWhere('slug->en', $slug);
    }

    public function courses(): HasMany
    {
        return $this->belongsToMany(Course::class);
    }
}
