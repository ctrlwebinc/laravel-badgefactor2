<?php

namespace Ctrlweb\BadgeFactor2\Models\Badgr;

use Ctrlweb\BadgeFactor2\Models\Badges\BadgePage;
use Ctrlweb\BadgeFactor2\Services\Badgr\Badge as BadgrBadge;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Badge extends Model
{
    use \Sushi\Sushi;
    use HasTranslations;

    protected $primaryKey = 'entityId';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $schema = [
        'entityId'          => 'string',
        'image'             => 'string',
        'issuer_id'         => 'string',
        'name'              => 'string',
        'description'       => 'string',
        'criteriaNarrative' => 'string',
        'badgeclass_id'     => 'string',
        'badgePage.title'   => 'json',
        'slug'              => 'json',
        'content'           => 'json',
        'criteria'          => 'json',
        'approval_type'     => 'string',
        'request_form_url'  => 'json',
        'badge_category_id' => 'integer',
        'course_id'         => 'integer',
        'last_updated_at'   => 'date',
        'created_at'        => 'datetime',
        'updated_at'        => 'datetime',
    ];

    protected $translatable = [
        'title',
        'slug',
        'content',
        'criteria',
        'request_form_url',
    ];

    protected static function booted(): void
    {
        static::creating(function (Badge $badge) {
            $badgeclassId = app(BadgrBadge::class)->add(
                $badge->image,
                $badge->name,
                $badge->issuer,
                $badge->description,
                $badge->criteriaNarrative
            );

            if (!$badgeclassId) {
                return false;
            }

            $badgePage = new BadgePage();
            $badgePage->badgeclass_id = $badgeclassId;
            $badgePage->title = request()->input('title');
            $badgePage->slug = request()->input('slug');
            $badgePage->content = request()->input('content');
            $badgePage->criteria = request()->input('criteria');
            $badgePage->approval_type = request()->input('badgePage.approval_type');
            $badgePage->request_form_url = request()->input('request_form_url');
            $badgePage->badge_category_id = request()->input('badgePage.badgeCategory');
            $badgePage->video_url = request()->input('video_url');
            $badgePage->last_updated_at = request()->input('badgePage.last_updated_at');
            $badgePage->saveQuietly();
            $badgePage->addMediaFromRequest('badgePage.__media__.image')
                ->preservingOriginal()
                ->toMediaCollection('image');

            return true;
        });

        static::updating(function (Badge $badge) {
            app(BadgrBadge::class)->update(
                $badge->entityId,
                $badge->name,
                $badge->issuer,
                $badge->description,
                $badge->criteriaNarrative,
                $badge->image
            );

            return true;
        });

        static::deleting(function (Badge $badge) {
            app(BadgrBadge::class)->delete(
                $badge->entityId
            );
            BadgePage::where('badgeclass_id', '=', $badge->entityId)->delete();

            return true;
        });
    }

    public function getRows()
    {
        $badges = app(BadgrBadge::class)->all();
        if ($badges) {
            $badges = collect(app(BadgrBadge::class)->all());

            $badgePages = BadgePage::with('course')->get();

            $badges = $badges->map(function ($row) use ($badgePages) {
                $row = collect($row);
                $row['issuer_id'] = $row['issuer'];
                unset($row['issuer']);

                $badgePage = $badgePages->where('badgeclass_id', $row['entityId'])->first();
                $row['badgeclass_id'] = !empty($badgePage) ? $badgePage->badgeclass_id : '';
                $row['title'] = !empty($badgePage) ? json_encode($badgePage->getTranslations('title')) : '';
                $row['slug'] = !empty($badgePage) ? json_encode($badgePage->getTranslations('slug')) : '';
                $row['content'] = !empty($badgePage) ? json_encode($badgePage->getTranslations('content')) : '';
                $row['criteria'] = !empty($badgePage) ? json_encode($badgePage->getTranslations('criteria')) : '';
                $row['approval_type'] = !empty($badgePage) ? $badgePage->approval_type : '';
                $row['request_form_url'] = !empty($badgePage) ? json_encode($badgePage->getTranslations('request_form_url')) : '';
                $row['badge_category_id'] = !empty($badgePage) ? $badgePage->badge_category_id : '';
                $row['course_id'] = !empty($badgePage) && !empty($badgePage->course) ? $badgePage->course->id : '';
                $row['last_updated_at'] = !empty($badgePage) && !empty($badgePage->last_updated_at) ? $badgePage->last_updated_at : '';

                return $row->except(['alignments', 'tags', 'extensions', 'expires'])
                    ->toArray();
            });

            return $badges->all();
        }

        return [];
    }

    public function assertions()
    {
        return $this->hasMany(Assertion::class, 'badgeclass_id');
    }

    public function issuer()
    {
        return $this->belongsTo(Issuer::class, 'issuer_id', 'entityId');
    }

    public function badgePage()
    {
        return $this->hasOne(BadgePage::class, 'badgeclass_id');
    }
}
