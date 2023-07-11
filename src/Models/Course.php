<?php

namespace Ctrlweb\BadgeFactor2\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'type',
        'duration',
        'url',
        'autoevaluation_form_url',
        'badge_page_id',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    public function badge()
    {
        return $this->belongsTo(Badge::class);
    }
}
