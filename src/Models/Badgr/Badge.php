<?php

namespace Ctrlweb\BadgeFactor2\Models\Badgr;

use Ctrlweb\BadgeFactor2\Models\Badges\BadgePage;
use Ctrlweb\BadgeFactor2\Services\Badgr\Badge as BadgrBadge;
use Illuminate\Database\Eloquent\Model;

class Badge extends Model
{
    use \Sushi\Sushi;

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
        'type'              => 'string',
        'title'             => 'json',
        'slug'              => 'json',
        'content'           => 'json',
        'criteria'          => 'json',
        'approval_type'     => 'string',
        'request_form_url'  => 'json',
        'badge_category_id' => 'integer',
        'badge_group_id'    => 'integer',
        'created_at'        => 'datetime',
        'updated_at'        => 'datetime',
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

            $badgePage = new BadgePage();
            $badgePage->badgeclass_id = $badgeclassId;
            $badgePage->type = $badge->type;
            $badgePage->title = $badge->title;
            $badgePage->slug = $badge->slug;
            $badgePage->content = $badge->content;
            $badgePage->criteria = $badge->criteria;
            $badgePage->approval_type = $badge->approval_type;
            $badgePage->request_form_url = $badge->request_form_url;
            $badgePage->badge_category_id = $badge->badge_category_id;
            $badgePage->badge_group_id = $badge->badge_group_id;
            $badgePage->saveQuietly();

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

            $badgePage = BadgePage::updateOrCreate(
                ['badgeclass_id' => $badge->entityId],
                [
                    'type'              => $badge->type,
                    'title'             => $badge->title,
                    'slug'              => $badge->slug,
                    'content'           => $badge->content,
                    'criteria'          => $badge->criteria,
                    'approval_type'     => $badge->approval_type,
                    'request_form_url'  => $badge->request_form_url,
                    'badge_category_id' => $badge->badge_category_id,
                    'badge_group_id'    => $badge->badge_group_id,
                ]
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

            $badgePages = BadgePage::all();

            $badges = $badges->map(function ($row) use ($badgePages) {
                $row = collect($row);
                $row['issuer_id'] = $row['issuer'];
                unset($row['issuer']);

                $badgePage = $badgePages->where('badgeclass_id', $row['entityId'])->first();
                $row['type'] = !empty($badgePage) ? $badgePage->type : null;
                $row['title'] = !empty($badgePage) ? json_encode($badgePage->getTranslations('title')) : null;
                $row['slug'] = !empty($badgePage) ? json_encode($badgePage->getTranslations('slug')) : null;
                $row['content'] = !empty($badgePage) ? json_encode($badgePage->getTranslations('content')) : null;
                $row['criteria'] = !empty($badgePage) ? json_encode($badgePage->getTranslations('criteria')) : null;
                $row['approval_type'] = !empty($badgePage) ? $badgePage->approval_type : null;
                $row['request_form_url'] = !empty($badgePage) ? json_encode($badgePage->getTranslations('request_form_url')) : null;
                $row['badge_category_id'] = !empty($badgePage) ? $badgePage->badge_category_id : null;
                $row['badge_group_id'] = !empty($badgePage) ? $badgePage->badge_group_id : null;

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
}
