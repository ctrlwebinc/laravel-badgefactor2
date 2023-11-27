<?php

namespace Ctrlweb\BadgeFactor2\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;

class BadgeCategory extends Model
{
    use HasFactory;
    use InteractsWithMedia;

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

    public function badges()
    {
        return $this->hasMany(Badge::class);
    }
}
