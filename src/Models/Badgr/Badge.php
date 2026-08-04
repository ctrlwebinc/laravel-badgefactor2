<?php

namespace Ctrlweb\BadgeFactor2\Models\Badgr;

use Ctrlweb\BadgeFactor2\Models\Badges\BadgePage;
use Ctrlweb\BadgeFactor2\Services\Badgr\Badge as BadgrBadge;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
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
        'expires'           => 'json',
        //'badgeclass_id'     => 'string',
        //'badgePage.title'   => 'json',
        //'slug'              => 'json',
        //'content'           => 'json',
        //'criteria'          => 'json',
        //'approval_type'     => 'string',
        //'request_form_url'  => 'json',
        //'badge_category_id' => 'integer',
        //'course_id'         => 'integer',
        //'last_updated_at'   => 'date',
        //'created_at'        => 'datetime',
        //'updated_at'        => 'datetime',
    ];

    protected $translatable = [
        //'title',
        //'slug',
        //'content',
        //'criteria',
        //'request_form_url',
    ];

    protected $casts = [
        'expires' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (Badge $badge) {
            $service = app(BadgrBadge::class);

            $badgeclassId = $service->add(
                $badge->image,
                $badge->name,
                $badge->issuer,
                $badge->description,
                $badge->criteriaNarrative,
                $badge->expires
            );

            if (!$badgeclassId) {
                // Returning false here silently cancels the save: the caller
                // gets a model that was never persisted, and Nova redirects
                // as if the badge had been created. Whoever tried to create
                // the badge is left with no badge and no explanation, which
                // is how this stayed unexplained for months. Fail loudly
                // instead, carrying the reason the API call gave us.
                throw ValidationException::withMessages([
                    'name' => $service->getLastError()
                        ?? 'La création du badge dans Badgr a échoué.',
                ]);
            }

            // Badgr assigns the entityId (this model's primary key) when the
            // badgeclass is created. Without writing it back, the saved model
            // keeps a null key: the API response returns "id": null, callers
            // can't reference the badge they just created, and anything keyed
            // on entityId (e.g. linking a BadgePage to badgeclass_id) silently
            // targets the wrong record.
            $badge->entityId = $badgeclassId;

            /*
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
            */

            return true;
        });

        static::updating(function (Badge $badge) {
            app(BadgrBadge::class)->update(
                $badge->entityId,
                $badge->name,
                $badge->issuer,
                $badge->description,
                $badge->criteriaNarrative,
                $badge->image,
                $badge->expires
            );

            /*
            $badgePage = BadgePage::updateOrCreate(
                ['badgeclass_id' => $badge->entityId],
                [
                    'title'             => $badge->badgePage['title'],
                    'slug'              => $badge->badgePage['slug'],
                    'content'           => $badge->badgePage['content'],
                    'criteria'          => $badge->badgePage['criteria'],
                    'approval_type'     => $badge->badgePage['approval_type'],
                    'request_form_url'  => $badge->badgePage['request_form_url'],
                    'badge_category_id' => $badge->badgePage['badge_category_id'],
                    'last_updated_at'   => $badge->badgePage['last_updated_at'],
                ]
            );
            */

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
        // Calling ->all() twice used to fetch the same data twice: once to
        // check it's truthy, once more (moments later) to actually use it.
        // The second call could land on a cache hit that the first call had
        // just populated, returning a differently-shaped payload than a
        // cache miss would (json_decode()'d stdClass objects vs the raw
        // API response). Fetch once and reuse it for both.
        $badges = app(BadgrBadge::class)->all();

        if ($badges) {
            $badges = collect($badges);

            $badgePages = BadgePage::with('course')->get();

            $badges = $badges->map(function ($row) {
                $row = collect($row);
                $row['issuer_id'] = $row['issuer'];
                unset($row['issuer']);

                $row['expires'] = json_encode( (array) $row['expires'] );
                
                /*
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
                */

                return $row->except(['alignments', 'tags', 'extensions'])
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
