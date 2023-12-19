<?php

namespace Ctrlweb\BadgeFactor2\Models;

use Ctrlweb\BadgeFactor2\Interfaces\TokenRepositoryInterface;
use Ctrlweb\BadgeFactor2\Models\Badgr\Assertion;
use Ctrlweb\BadgeFactor2\Models\Courses\Course;
use Ctrlweb\BadgeFactor2\Notifications\ResetPasswordNotification;
use Ctrlweb\BadgeFactor2\Services\Badgr\User as BadgrUser;
use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use League\OAuth2\Client\Token\AccessTokenInterface;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail, HasMedia, TokenRepositoryInterface
{
    use HasApiTokens;
    use HasFactory;
    use HasRoles;
    use InteractsWithMedia;
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
        'stripe_id',
        'pm_type',
        'pm_last_four',
        'trial_ends_at',
        'first_name',
        'last_name',
        'description',
        'website',
        'slug',
        'wp_id',
        'wp_password',
        'username',
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
        'badgr_token_set',
        'last_connexion',
        'badgr_user_state',
        'badgr_user_slug',
        'badgr_password',
        'badgr_encrypted_password',
        'establishment_id',

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
        'badgr_token_set',
        'badgr_password',
        'badgr_encrypted_password',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_at'                  => 'datetime',
        'updated_at'                  => 'datetime',
        'email_verified_at'           => 'datetime',
        'is_validated'                => 'boolean',
        'badgr_encrypted_password'    => 'encrypted',
    ];

    protected $with = [
        'roles', 'billingInfo',
    ];

    protected $guard_name = 'web';

    public static function boot()
    {
        parent::boot();

        self::creating(function (User $user) {
            if ('' == $user->badgr_encrypted_password) {
                $user->badgr_encrypted_password = Str::random(16);
            }
            $user->slug = Str::slug($user->username);

            $user->badgr_user_slug = (new BadgrUser())->add(
                $user->first_name,
                $user->last_name,
                $user->email,
                $user->badgr_encrypted_password
            );
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
        return $this->hasMany(Assertion::class, 'recipient_id');
    }

    public function isVerified(): Attribute
    {
        $isVerified = $this->badgr_user_slug ? app(BadgrUser::class)->hasVerifiedEmail($this->badgr_user_slug) : false;

        return Attribute::make(
            get: fn ($value) => $isVerified,
        );
    }

    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(32)
            ->height(32);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('photo')->singleFile();
    }

    public function getTokenSet(): ?AccessTokenInterface
    {
        $tokenSet = unserialize($this->badgr_token_set);
        if (!$tokenSet) {
            return null;
        }

        return $tokenSet;
    }

    public function saveTokenSet(AccessTokenInterface $tokenSet)
    {
        $this->refresh();
        $this->badgr_token_set = serialize($tokenSet);
        $this->save();
    }
}
