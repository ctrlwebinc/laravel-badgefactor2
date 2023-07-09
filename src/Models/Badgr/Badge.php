<?php

namespace Ctrlweb\BadgeFactor2\Models\Badgr;

use App\Models\BadgrConfig;
use Collator;
use Ctrlweb\BadgeFactor2\Services\Badgr\Badge as BadgrBadge;
use Ctrlweb\BadgeFactor2\Services\Badgr\BadgrService;
use Illuminate\Database\Eloquent\Model;

class Badge extends Model
{
    use \Sushi\Sushi;

    protected $primaryKey = 'entityId';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $schema = [
        'entityId' => 'string',
        'image' => 'string',
        'issuer_id' => 'string',
        'name' => 'string',
        'description' => 'string',
        'criteriaNarrative' => 'string',
    ];

    protected static function booted(): void
    {
        static::creating(function (Badge $badge) {
            app(BadgrBadge::class)->add(
                $badge->image,
                $badge->name,
                $badge->issuer,
                $badge->description,
                $badge->criteriaNarrative
            );
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
            return true;
        });
    }

    public function getRows()
    {
        $badges = app(BadgrBadge::class)->all();
        if ($badges) {
            $badges = collect(app(BadgrBadge::class)->all());
            $badges = $badges->map(function($row) {
                $row = collect($row);
                $row['issuer_id'] = $row['issuer'];
                unset($row['issuer']);
                return $row->except(['alignments', 'tags', 'extensions', 'expires'])
                    ->toArray();
            });

            return $badges->all();
        }
        return [];

    }

    public function issuer()
    {
        return $this->belongsTo(Issuer::class, 'issuer_id', 'entityId');
    }

}
