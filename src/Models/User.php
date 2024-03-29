<?php

namespace Ctrlweb\Badgefactor2\Models;

use Ctrlweb\BadgeFactor2\Models\Badgr\Assertion;
use Ctrlweb\BadgeFactor2\Models\BillingInfo;
use Ctrlweb\BadgeFactor2\Models\Courses\Course;
use Ctrlweb\BadgeFactor2\Notifications\ResetPasswordNotification;
use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens;
    use HasFactory;
    use HasRoles;
    use Notifiable;

    const ADMINISTRATOR = 'administrator';

    const EDITOR = 'editor';

    const LEARNER = 'learner';

    const CLIENT = 'client';

    const APPROVER = 'approver';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [

        'id',
        'email',
        'email_verified_at',
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'two_factor_confirmed_at',
        'remember_token',
        'created_at',
        'updated_at',
        'first_name',
        'last_name',
        'description',
        'website',
        'slug',
        'wp_id',
        'wp_password',
        'establishment_id',
        'place',
        'organisation',
        'job',
        'biography',
        'facebook',
        'twitter',
        'linkedin',
        'photo',
        'billing_last_name',
        'billing_first_name',
        'billing_society',
        'billing_address_line_1',
        'billing_address_line_2',
        'billing_city',
        'billing_postal_code',
        'billing_country',
        'billing_state',
        'billing_phone',
        'billing_email',
        'user_status',
        'last_connexion',
        'username',
        'stripe_id',
        'pm_type',
        'pm_last_four',
        'trial_ends_at',
        'wp_application_password',
        'badgr_user_state',
        'badgr_user_slug',
        'badgr_password',
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
        'created_at'        => 'datetime',
        'email_verified_at' => 'datetime',
    ];

    protected $with = [
        'roles', 'billingInfo',
    ];

    public static function boot()
    {
        parent::boot();

        self::creating(function (User $user) {
            $user->name = $user->first_name.' '.$user->first_name;
            $user->password = Hash::make($user->password ?? Str::random(16));
            $user->slug = Str::slug($user->username);
        });

        self::created(function (User $user) {
            if (!$user->roles()->count()) {
                tap(Role::whereName(User::LEARNER)->first(), fn ($role) => $user->assignRole($role));
            }

            event(new Registered($user));
        });

        self::addGlobalScope('query', function ($query) {
            if (request('q')) {
                $query->whereRaw("concat(first_name, ' ', last_name) LIKE '%".request('q')."%'")
                    ->orWhereRaw("concat(last_name, ' ', first_name) LIKE '%".request('q')."%'");
            }
        });

        self::addGlobalScope('order', function ($query) {
            if (request('order')) {
                $query->orderBy(request('order'));
            }
        });

        // Filter by dates (start and end)
        self::addGlobalScope('dates', function ($query) {
            if (request('start_date')) {
                $query->where('created_at', '>=', request('start_date'));
            }

            if (request('end_date')) {
                $query->where('created_at', '<=', request('end_date'));
            }
        });
    }

    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    public function primaryRole(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->roles->first()->name
        );
    }

    public function fullname(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->first_name.' '.$this->last_name
        );
    }

    public function courses()
    {
        return $this->belongsToMany(Course::class);
    }

    public function billingInfo()
    {
        return $this->hasOne(BillingInfo::class);
    }

    public function assertions()
    {
        return $this->hasMany(Assertion::class);
    }
}
