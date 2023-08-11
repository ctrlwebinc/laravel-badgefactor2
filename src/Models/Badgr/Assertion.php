<?php

namespace Ctrlweb\BadgeFactor2\Models\Badgr;

use Ctrlweb\BadgeFactor2\Models\User;
use Ctrlweb\BadgeFactor2\Services\Badgr\Assertion as BadgrAssertion;
use Illuminate\Database\Eloquent\Model;

class Assertion extends Model
{
    use \Sushi\Sushi;

    protected $primaryKey = 'entityId';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $casts = [
        'issedOn' => 'datetime',
        'expires' => 'datetime',
    ];

    protected $schema = [
        'entityId'         => 'string',
        'badgeclass_id'    => 'string',
        'issuer_id'        => 'string',
        'image'            => 'string',
        'recipient_email'  => 'string',
        'recipient_id'     => 'integer',
        'issuedOn'         => 'dateTime',
        'narrative'        => 'string',
        'evidenceUrl'      => 'string',
        'evidenceNarrative'      => 'string',
        'revoked'          => 'boolean',
        'revocationReason' => 'string',
        'expires'          => 'dateTime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $assertion) {
            $badgeclassId = app(BadgrAssertion::class)->add(
                $assertion->issuer,
                $assertion->badgeClass,
                $assertion->recipient,
                'email',
                $assertion->issuedOn,
                $assertion->evidenceUrl,
                $assertion->evidenceNarrative
            );

            if (!$badgeclassId) {
                return false;
            }
        });

        static::updating(function (self $assertion) {
        });

        static::deleting(function (self $assertion) {
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

                if (isset($assertion['evidence'][0]['url'])) {
                    $assertions[$i]['evidenceUrl'] = $assertions[$i]['evidence'][0]['url'];
                }
                if (isset($assertion['evidence'][0]['narrative'])) {
                    $assertions[$i]['evidenceNarrative'] = $assertions[$i]['evidence'][0]['narrative'];
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
