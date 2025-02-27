<?php

namespace Ctrlweb\BadgeFactor2\Models;

use Carbon\Carbon;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use Spatie\MediaLibrary\HasMedia;
use Spatie\Permission\Models\Role;
use Illuminate\Auth\Events\Registered;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Notifications\Notifiable;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Ctrlweb\BadgeFactor2\Models\Courses\Course;
use Ctrlweb\BadgeFactor2\Models\Badgr\Assertion;
use Ctrlweb\BadgeFactor2\Models\Badges\BadgePage;
use Illuminate\Database\Eloquent\Casts\Attribute;
use League\OAuth2\Client\Token\AccessTokenInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Ctrlweb\BadgeFactor2\Services\Badgr\User as BadgrUser;
use Ctrlweb\BadgeFactor2\Interfaces\TokenRepositoryInterface;
use Ctrlweb\BadgeFactor2\Services\Badgr\BadgrRecipientProvider;
use Ctrlweb\BadgeFactor2\Notifications\ResetPasswordNotification;

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
        'new_email',
        'new_email_validation_token',
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
        'description_visible',
        'website',
        'website_visible',
        'slug',
        'wp_id',
        'wp_password',
        'username',
        'place',
        'place_visible',
        'organisation',
        'organisation_visible',
        'job',
        'new_job',
        'job_visible',
        'biography',
        'biography_visible',
        'facebook',
        'facebook_visible',
        'twitter',
        'twitter_visible',
        'linkedin',
        'linkedin_visible',
        'photo',
        'user_status',
        'badgr_token_set',
        'last_connexion',
        'badgr_user_state',
        'badgr_user_slug',
        'badgr_password',
        'badgr_encrypted_password',
        'establishment_id',
        'new_establishment_id',
        'checked_at'
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
        'description_visible'         => 'boolean',
        'website_visible'             => 'boolean',
        'place_visible'               => 'boolean',
        'organisation_visible'        => 'boolean',
        'job_visible'                 => 'boolean',
        'biography_visible'           => 'boolean',
        'facebook_visible'            => 'boolean',
        'twitter_visible'             => 'boolean',
        'linkedin_visible'            => 'boolean',
    ];

    protected $with = [
        'roles',
    ];

    protected $guard_name = 'web';

    protected $appends = ['is_checked']; 


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

    public function getIsCheckedAttribute(): bool
    {
        return $this->checked_at && Carbon::parse($this->checked_at)->isToday() || Carbon::parse($this->checked_at)->isFuture();
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

    public function assertions()
    {
        return $this->hasMany(Assertion::class, 'recipient_id');
    }

    public function badge_pages()
    {
        return $this->belongsToMany(BadgePage::class, 'approver_badge_page', 'approver_id', 'badge_page_id');
    }

    public function isVerified(): Attribute
    {
        $isVerified = false;
        if ($this->hasVerifiedEmail()) {
            $isVerified = true;
        } else {
            $badgrUser = User::find($this->id);

            if (null !== $badgrUser) {
                $recipientService = new BadgrRecipientProvider($badgrUser);
                if ($recipientService->hasVerifiedEmail()) {
                    $this->markEmailAsVerified();
                    $isVerified = true;
                }
            }
        }

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
