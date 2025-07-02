<?php

namespace Ctrlweb\BadgeFactor2\Models\Badges;

use Carbon\Carbon;
use Laravel\Scout\Searchable;
use Spatie\MediaLibrary\HasMedia;
use Ctrlweb\BadgeFactor2\Models\User;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;
use Illuminate\Database\Eloquent\Builder;
use Spatie\MediaLibrary\InteractsWithMedia;
use Ctrlweb\BadgeFactor2\Models\Badgr\Badge;
use Ctrlweb\BadgeFactor2\Models\Courses\Course;
use Ctrlweb\BadgeFactor2\Models\Courses\CourseGroup;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Ctrlweb\BadgeFactor2\Services\Badgr\Badge as BadgrBadge;
use App\Helpers\CacheHelper;
use Illuminate\Database\Eloquent\Relations\Pivot;

class BadgePage extends Model implements HasMedia
{
    use HasTranslations;
    use InteractsWithMedia;
    use Searchable;

    protected $casts = [
        'badgeclass_id'   => 'string',
        'last_updated_at' => 'date',
        'publication_date' => 'datetime'
    ];

    protected $fillable = [
        'badgeclass_id',
        'title',
        'slug',
        'content',
        'criteria',
        'approval_type',
        'request_type',
        'request_form_url',
        'badge_category_id',
        'video_url',
        'last_updated_at',
        'publication_date',
        'status',
        'is_hidden',
        'is_featured',
        'meta_title',
        'meta_description'
    ];

    public static function findBySlug($slug)
    {
        return self::where('slug->fr', $slug)
            ->orWhere('slug->en', $slug);
    }

    public function searchableAs()
    {
        return 'badge_page_index';
    }

    public function toSearchableArray()
    {
        return [
            'id' => (string) $this->id,
            'title' => $this->title,
            'created_at' => $this->created_at?->timestamp
        ];
    }

    protected $translatable = [
        'title',
        'slug',
        'content',
        'criteria',
        'request_form_url',
        'video_url',
        'meta_title',
        'meta_description'
    ];

    protected $appends = ['badge'];

    public static function boot()
    {
        parent::boot();

        self::addGlobalScope('badgeCategory', function (Builder $query) {
            if (request('badge_category') && !request('badge_categories')) {
                $locale = app()->getLocale();
                $badgeCategory = request()->input('badge_category');
                $query->whereHas('badgeCategory', function (Builder $q) use ($badgeCategory, $locale) {
                    $q->where("slug->{$locale}", '=', $badgeCategory);
                });
            }
        });

        self::addGlobalScope('courseGroup', function (Builder $query) {
            if (request('course_group')) {
                $courseGroup = request('course_group');
                if ($courseGroup instanceof CourseGroup) {
                    $query->whereHas('course', function ($query) use ($courseGroup) {
                        $query->where('course_group_id', $courseGroup->id);
                    });
                } else {
                    $query->whereHas('course', function ($query) {
                        $query->where('course_group_id', request('course_group'));
                    });
                }
            }
        });

        self::addGlobalScope('issuer', function (Builder $query) {
            if (request('issuer')) {
                $issuer = request('issuer');
                $badgrBadge = app(BadgrBadge::class);

                $badges = collect($badgrBadge->getByIssuer($issuer));

                $badgeClassIds = $badges->pluck('entityId')->toArray();

                $badges = $query->whereIn('badgeclass_id', $badgeClassIds);
            }
        });

        self::addGlobalScope('q', function (Builder $query) {
            if (request('q')) {
                $keywords = strtolower(request('q'));
                $locale = app()->getLocale();
                $query->whereRaw(
                    "LOWER(
                    CONVERT(title->'$.$locale' USING utf8mb4)) LIKE ?",
                    "%{$keywords}%"
                )->orWhereRaw(
                    "LOWER(
                    CONVERT(slug->'$.$locale' USING utf8mb4)) LIKE ?",
                    "%{$keywords}%"
                )->orWhereRaw(
                    "LOWER(
                    CONVERT(content->'$.$locale' USING utf8mb4)) LIKE ?",
                    "%{$keywords}%"
                )->orWhereHas('course', function ($query) use ($keywords, $locale) {
                    $query->whereRaw(
                        "LOWER(
                        CONVERT(title->'$.$locale' USING utf8mb4)) LIKE ?",
                        "%{$keywords}%"
                    );
                });
            }
        });

        $caches = ['search_engine_response', 'badge_category_certification', 'badge_pages', 'badge_categories', 'tag_groups'];        

        foreach ($caches as $key => $cache) {

            static::saved(function () use ($cache) {
                CacheHelper::forgetGroup($cache);
            });
    
            static::updated(function () use ($cache) {
                CacheHelper::forgetGroup($cache);
            });
        
            static::deleted(function () use ($cache) {
                CacheHelper::forgetGroup($cache);
            });

            Pivot::created(function($pivot) use ($cache) {
                CacheHelper::forgetGroup($cache);
            });
    
            Pivot::updated(function($pivot) use ($cache) {
                CacheHelper::forgetGroup($cache);
            });
    
            Pivot::deleted(function($pivot) use ($cache) {
                CacheHelper::forgetGroup($cache);
            });
        }

        
    }

    public function approvers()
    {
        return $this->belongsToMany(User::class, 'approver_badge_page', 'badge_page_id', 'approver_id');
    }

    public function course()
    {
        return $this->hasOne(Course::class);
    }

    public function badgeCategory()
    {
        return $this->belongsTo(BadgeCategory::class);
    }

    public function badge()
    {
        return $this->belongsTo(Badge::class, 'badgeclass_id', 'entityId');
    }

    public function getBadgeAttribute()
    {
        if ($this->badgeclass_id) {
            $badge = app(BadgrBadge::class)->getBySlug($this->badgeclass_id);
            
            if (is_array($badge)) {
                return Badge::where('entityId', '=', $badge['entityId'])->first();
            } else if( isset($badge->entityId) ) {
                return Badge::where('entityId', '=', $badge->entityId)->first();
            }
        }

        return null;
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('image')->singleFile();
    }

    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(32)
            ->height(32);
    }

    public function scopeIsPublished($query)
    {
        return $query->where('status', 'PUBLISHED')
                    ->where('updated_at', '>=', now()->subYears(3));
    }

    public static function takeOnlyBrandnew()
    {
        return self::withoutGlobalScopes(['issuer'])->where('is_hidden', false)->isPublished()->orderBy('created_at', 'desc') 
                        ->take(10)->get();
    }

    public function scopeIsBrandnew($query)
    {
        $brandnews = self::takeOnlyBrandnew()->map(function($line){
            return $line->id;
        })->toArray();

        return $query->whereIn("id", $brandnews); 
    }

    public function getIsBrandnewAttribute()
    {     
        return self::takeOnlyBrandnew()->contains($this);
    }
}
