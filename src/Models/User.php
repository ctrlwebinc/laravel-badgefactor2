<?php

namespace Ctrlweb\Badgefactor2\Models;

use Ctrlweb\BadgeFactor2\Models\BillingInfo;
use Ctrlweb\BadgeFactor2\Models\Course;
use Ctrlweb\BadgeFactor2\Models\UserRole;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'first_name',
        'last_name',
        'email',
        'password',
        'description',
        'website',
        'email',
        'email_verified_at',
        'password',
        'slug',
        'created_at',
        'wp_id',
        'wp_password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'wp_password',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
        'email_verified_at' => 'datetime',
    ];

    protected $with = [
        'roles', 'billingInfo'
    ];

    public function courses()
    {
        return $this->belongsToMany(Course::class);
    }

    public function roles()
    {
        return $this->hasMany(UserRole::class);
    }

    public function billingInfo()
    {
        return $this->hasOne(BillingInfo::class);
    }
}
