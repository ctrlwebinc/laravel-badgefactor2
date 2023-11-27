<?php

namespace Ctrlweb\BadgeFactor2\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BadgeCategory extends Model
{
    use HasFactory;

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
