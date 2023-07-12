<?php

namespace Ctrlweb\BadgeFactor2\Models\Courses;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

class Responsible extends Model
{
    use HasFactory, HasTranslations;

    protected $translatable = [
        'slug',
        'description',
    ];

    public function courseGroups()
    {
        return $this->belongsToMany(CourseGroup::class);
    }
}
