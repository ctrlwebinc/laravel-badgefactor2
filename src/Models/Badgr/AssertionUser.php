<?php

namespace Ctrlweb\BadgeFactor2\Models\Badgr;

use Ctrlweb\BadgeFactor2\Events\AssertionIssued;
use Ctrlweb\BadgeFactor2\Models\User;
use Ctrlweb\BadgeFactor2\Services\Badgr\Assertion as BadgrAssertion;
use Ctrlweb\BadgeFactor2\Services\Badgr\BackpackAssertion;
use Illuminate\Database\Eloquent\Model;

class AssertionUser extends Model
{

    protected $table = 'assertion_user';

    protected $casts = [
        'user_id' => 'integer',
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
