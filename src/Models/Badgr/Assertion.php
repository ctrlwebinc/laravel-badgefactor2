<?php

namespace Ctrlweb\BadgeFactor2\Models\Badgr;

use Ctrlweb\BadgeFactor2\Models\User;
use Ctrlweb\BadgeFactor2\Services\Badgr\Assertion as BadgrAssertion;
use Ctrlweb\BadgeFactor2\Services\Badgr\BadgrService;
use Illuminate\Database\Eloquent\Model;
use Laravel\Nova\FilterDecoder;

class Assertion extends Model
{
    use \Sushi\Sushi;

    protected $primaryKey = 'entityId';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $schema = [
        'entityId' => 'string',
        'badgeclass_id' => 'string',
        'issuer_id' => 'string',
        'image' => 'string',
        'recipient_email' => 'string',
        'recipient_id' => 'integer',
        'issuedOn' => 'string',
        'narrative' => 'string',
        'evidenceUrl' => 'string',
        'revoked' => 'boolean',
        'revocationReason' => 'string',
        'expires' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Badge $badge) {

        });

        static::updating(function (Badge $badge) {

        });

        static::deleting(function (Badge $badge) {

        });
    }

    public function getRows()
    {
        $viaResource = request()->get('viaResource');
        $viaResourceId = request()->get('viaResourceId');

        if (!$viaResource) {
            if (str_contains(request()->getPathInfo(), '/issuers/')) {
                $viaResource = 'issuers';
                $arr = explode('/issuers/', request()->getPathInfo());
                $viaResourceId = end($arr);

            } elseif (str_contains(request()->getPathInfo(), '/badges/')) {
                $viaResource = 'badges';
                $arr = explode('/badges/', request()->getPathInfo());
                $viaResourceId = end($arr);
            }
        }

        $isFiltered = false;
        $assertions = [];

        if ($viaResource && $viaResourceId) {

            switch ($viaResource) {
                case 'issuers':
                    $isFiltered = true;
                    $assertions = app(BadgrAssertion::class)->getByIssuer($viaResourceId);
                    break;
                case 'badges':
                    $isFiltered = true;
                    $assertions = app(BadgrAssertion::class)->getByBadgeClass($viaResourceId);
                    break;
            }
        }

        if (!$isFiltered) {
            return [];
        }

        if ($assertions) {
            foreach ($assertions as $i => $assertion) {
                unset($assertions[$i]['entityType']);
                unset($assertions[$i]['openBadgeId']);
                unset($assertions[$i]['badgeclassOpenBadgeId']);
                unset($assertions[$i]['issuerOpenBadgeId']);
                $assertions[$i]['recipient_email'] = $assertions[$i]['recipient']['plaintextIdentity'];
                unset($assertions[$i]['recipient']);
                $recipient = User::where('email', '=', $assertions[$i]['recipient_email'])->first();
                $assertions[$i]['recipient_id'] = $recipient->id ?? null;

                if (!isset($assertion['evidence'][0])) {
                    $assertions[$i]['evidenceUrl'] = null;
                } else {
                    $assertions[$i]['evidenceUrl'] = $assertions[$i]['evidence'][0]['url'];
                }
                unset($assertions[$i]['evidence']);
                unset($assertions[$i]['acceptance']);
                unset($assertions[$i]['extensions']);
                $assertions[$i]['issuer_id'] = $assertions[$i]['issuer'];
                unset($assertions[$i]['issuer']);
                $assertions[$i]['badgeclass_id'] = $assertions[$i]['badgeclass'];
                unset($assertions[$i]['badgeclass']);
                unset($assertions[$i]['createdAt']);
                unset($assertions[$i]['createdBy']);
            }
        }

        return $assertions;

    }

    public function issuer()
    {
        return $this->belongsTo(Issuer::class, 'issuer_id', 'entityId');
    }


    public function badgeclass()
    {
        return $this->belongsTo(Badge::class, 'badgeclass_id', 'entityId');
    }

    public function recipient()
    {
        return $this->belongsTo(User::class, 'recipient_id', 'id');
    }

}
