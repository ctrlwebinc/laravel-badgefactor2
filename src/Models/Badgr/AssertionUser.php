<?php

namespace Ctrlweb\BadgeFactor2\Models\Badgr;

use Illuminate\Database\Eloquent\Model;

class AssertionUser extends Model
{
    protected $table = 'assertion_user';

    protected $casts = [
        'user_id'    => 'integer',
        'is_visible' => 'boolean',
    ];

    protected $fillable = [
        'assertion_id',
        'user_id',
        'is_visible',
    ];

    public static function boot()
    {
        parent::boot();
    }
}
