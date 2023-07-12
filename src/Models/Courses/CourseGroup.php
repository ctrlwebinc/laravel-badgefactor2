<?php

namespace Ctrlweb\BadgeFactor2\Models\Courses;

use Ctrlweb\BadgeFactor2\Services\Badgr\BadgrService;
use Ctrlweb\BadgeFactor2\Services\Badgr\Badge as BadgrBadge;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

class CourseGroup extends Model
{
    use HasTranslations;

    protected $fillable = [
        'slug',
        'title',
        'excerpt',
        'subtitle',
        'image',
        'description',
    ];

    protected $translatable = [
        'slug',
        'excerpt',
        'title',
        'subtitle',
        'description',
    ];

    public static function findBySlug($slug)
    {
        return self::where('slug->fr', $slug)
            ->orWhere('slug->en', $slug);
    }

    public function courseGroupCategory(): BelongsTo
    {
        return $this->belongsTo(CourseGroupCategory::class);
    }

    public function contentSpecialists()
    {
        return $this->belongsToMany(Responsible::class, 'content_specialist_course_group');
    }

    public function retroactionResponsibles()
    {
        return $this->belongsToMany(Responsible::class, 'course_group_retroaction_responsible');
    }

    public function courses(): HasMany
    {
        return $this->hasMany(Course::class);
    }

    public static function boot()
    {
        parent::boot();

        self::addGlobalScope('q', function (Builder $query) {
            $locale = app()->getLocale();
            if (request('q')) {
                $query->where(function( $q) {
                    $q->where("title", 'LIKE', '%'.request('q').'%')
                        ->orWhere("description", 'LIKE', '%'.request('q').'%');
                });
            }
        });

        self::addGlobalScope('course_group_category', function ($query) {
            if (request('course_group_category')) {
                $query->where('course_group_category_id', request('course_group_category'));
            }
        });

        self::addGlobalScope('issuer', function ($query) {
            $locale = app()->getLocale();
            $query->when(!empty(request()->input('issuer')), function ($q) use ($locale) {
                $issuer = request()->input('issuer');
                $badgeClassIds = collect(app(BadgrBadge::class)->getByIssuer($issuer))->pluck('entityId')->toArray();
                $badgePageIds = BadgePage::withoutGlobalScope('issuer')->withoutGlobalScope('q')->whereIn('badgeclass_id', $badgeClassIds)->pluck('id');
                $courseGroupIds = Course::whereIn('badge_page_id', $badgePageIds)->pluck('course_group_id');
                return $q->whereIn('id', $courseGroupIds);
            });
        });
    }
}
