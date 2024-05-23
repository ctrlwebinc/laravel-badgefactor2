<?php

namespace Ctrlweb\BadgeFactor2\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class BadgeCategory extends Model
{
    use HasFactory;
    use HasTranslations;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'subtitle',
        'description',
        'slug',
    ];

    protected $translatable = [
        'title',
        'description',
        'slug',
    ];

    public function badges()
    {
        return $this->hasMany(Badge::class);
    }
}
