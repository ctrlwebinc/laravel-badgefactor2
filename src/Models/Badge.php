<?php

namespace Ctrlweb\BadgeFactor2\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Badge extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'badgeclass_id',
        'type',
        'title',
        'slug',
        'content',
        'criteria',
        'approval_type',
        'request_type',
        'request_form_url',
    ];

    public function course()
    {
        return $this->hasOne(Course::class);
    }

    public function category()
    {
        return $this->belongsTo(BadgeCategory::class);
    }

    public function group()
    {
        return $this->belongsTo(BadgeGroup::class);
    }
}
