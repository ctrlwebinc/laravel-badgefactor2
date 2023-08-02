<?php

namespace Ctrlweb\BadgeFactor2\Models\Badges;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class TargetAudience extends Model
{
    use HasFactory;
    use HasTranslations;

    protected $fillable = [
        'name',
        'slug',
        'description',
    ];

    protected $translatable = [
        'name',
        'slug',
        'description',
    ];

    public function badgePages()
    {
        return $this->belongsToMany(BadgePage::class);
    }
}
