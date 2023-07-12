<?php

namespace Ctrlweb\BadgeFactor2\Models\Badges;

use Ctrlweb\BadgeFactor2\Services\Badgr\Badge as BadgrBadge;
use Ctrlweb\BadgeFactor2\Services\Badgr\Issuer as BadgrIssuer;
use Ctrlweb\BadgeFactor2\Models\Courses\Course;
use Ctrlweb\BadgeFactor2\Models\Courses\CourseGroup;
use Collator;
use Ctrlweb\BadgeFactor2\Services\Badgr\BadgrProvider;
use Illuminate\Database\Eloquent\Builder;
use Ctrlweb\BadgeFactor2\Models\Badgr\Badge as BadgeFactor2Badge;
use Spatie\Translatable\HasTranslations;
use Illuminate\Database\Eloquent\Model;

class BadgePage extends Model
{
    use HasTranslations;

    protected $casts = [
        'last_updated_at' => 'datetime',
        'badgeclass_id' => 'string',
    ];

    public static function findBySlug($slug)
    {
        return self::where('slug->fr', $slug)
            ->orWhere('slug->en', $slug);
    }

    protected $translatable = [
        'title',
        'slug',
        'content',
        'criteria',
        'request_form_url',
    ];

    protected $appends = ['badge'];

    public static function boot()
    {
        parent::boot();

        self::addGlobalScope('badgeCategory', function (Builder $query) {
            if (request('badge_category')) {
                $query->where('type', request('badge_category'));
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
    }

    public function scopeExcludeCertification()
    {
        return $this->whereNot('type', 'certification');
    }

    public function scopeCertification($query)
    {
        return $query->where('type', 'certification');
    }

    public function course()
    {
        return $this->hasOne(Course::class);
    }

    public function courseCategory()
    {
        return $this->belongsTo(CourseCategory::class);
    }

    public function getBadgeAttribute()
    {
        if ($this->badgeclass_id)
        {
            $badge = app(BadgrBadge::class)->getBySlug($this->badgeclass_id);
            return $badge;
        }
        return null;
    }
}
