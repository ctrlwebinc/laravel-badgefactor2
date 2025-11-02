<?php

namespace Ctrlweb\BadgeFactor2\Models\Badgr;

use Ctrlweb\BadgeFactor2\Events\AssertionIssued;
use Ctrlweb\BadgeFactor2\Models\User;
use Ctrlweb\BadgeFactor2\Services\Badgr\Assertion as BadgrAssertion;
use Ctrlweb\BadgeFactor2\Services\Badgr\BackpackAssertion;
use Illuminate\Database\Eloquent\Model;

class Assertion extends Model
{
    use \Sushi\Sushi;

    protected $primaryKey = 'entityId';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $casts = [
        'issuedOn' => 'datetime',
        'expires' => 'datetime',
    ];

    protected $schema = [
        'entityId'               => 'string',
        'badgeclass_id'          => 'string',
        'issuer_id'              => 'string',
        'image'                  => 'string',
        'recipient_email'        => 'string',
        'recipient_id'           => 'integer',
        'issuedOn'               => 'dateTime',
        'narrative'              => 'string',
        'evidenceUrl'            => 'string',
        'evidenceNarrative'      => 'string',
        'revoked'                => 'boolean',
        'revocationReason'       => 'string',
        'expires'                => 'dateTime',
    ];

    protected $fillable = [
        'entityId',
        'badgeclass_id',
        'issuer_id',
        'image',
        'recipient_email',
        'recipient_id',
        'issuedOn',
        'narrative',
        'evidenceUrl',
        'evidenceNarrative',
        'revoked',
        'revocationReason',
        'expires',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $assertion) {

            $assertionId = app(BadgrAssertion::class)->add(
                $assertion->issuer,
                $assertion->badgeclass,
                $assertion->recipient,
                'email',
                $assertion->issuedOn,
                $assertion->evidenceUrl,
                $assertion->evidenceNarrative,
                $assertion->expires
            );

            if( !$assertionId ){
                return false;
            }

            $assertion->entityId = $assertionId;
            
            return true;
        });

        static::updating(function (self $assertion) {            
            return app(BadgrAssertion::class)->update(
                $assertion->entityId,
                [
                    'recipient'         => $assertion->recipient,
                    'issuedOn'          => $assertion->issuedOn,
                    'evidenceNarrative' => $assertion->evidenceNarrative,
                    'evidenceUrl'       => $assertion->evidenceUrl,
                    'expires'       => $assertion->expires,
                ]
            );
        });

        static::deleting(function (self $assertion) {
            return app(BadgrAssertion::class)->revoke($assertion->entityId);
        });
    }

    /**
     * The event map for the model.
     *
     * @var array
     */
    protected $dispatchesEvents = [
        'created' => AssertionIssued::class,
    ];

    public function getRows()
    {
        $resourceId = request()->get('resourceId');
        if ($resourceId) {
            $viaResource = 'direct';
            $viaResourceId = $resourceId;
        } else {
            $viaResource = request()->get('viaResource');
            $viaResourceId = request()->get('viaResourceId');
        }

        if (!$viaResource) {
            if (str_contains(request()->getPathInfo(), '/issuers/')) {
                $viaResource = 'issuers';
                $arr = explode('/issuers/', request()->getPathInfo());
                $viaResourceId = end($arr);
            } elseif (str_contains(request()->getPathInfo(), '/badges/')) {
                $viaResource = 'badges';
                $arr = explode('/badges/', request()->getPathInfo());
                $viaResourceId = end($arr);
            } elseif (str_contains(request()->getPathInfo(), '/admin/resources/assertions/')) {
                $viaResource = 'direct';
                $arr = explode('/admin/resources/assertions/', request()->getPathInfo());
                $viaResourceId = end($arr);
            } elseif (str_contains(request()->getPathInfo(), '/nova-api/assertions/')) {
                $viaResource = 'direct';
                $arr = explode('/nova-api/assertions/', request()->getPathInfo());
                $viaResourceId = end($arr);
            } elseif (str_contains(request()->getPathInfo(), '/admin/resources/learners/')) {
                $viaResource = 'learners';
                $arr = explode('/admin/resources/learners/', request()->getPathInfo());
                $viaResourceId = end($arr);
            } elseif (str_contains(request()->getPathInfo(), '/backpack-assertions/')) {
                $viaResource = 'learnerSlug';
                $arr = explode('/backpack-assertions/', request()->getPathInfo());
                $viaResourceId = end($arr);
            } elseif (str_contains(request()->getPathInfo(), '/backpack/assertions/')) {
                $viaResource = 'learnerEmail';
                $arr = explode('/backpack/assertions/', request()->getPathInfo());
                $viaResourceId = urldecode(end($arr));
            } elseif (str_contains(request()->getPathInfo(), '/api/fr/assertions/')) {
                $viaResource = 'direct';
                $arr = explode('/', request()->getPathInfo());
                $viaResourceId = $arr[4];
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
                case 'direct':
                    $isFiltered = true;
                    $assertions = json_decode(json_encode(app(BadgrAssertion::class)->getBySlug($viaResourceId)), true);
                    $assertions = false === $assertions ? [] : [$assertions];
                    break;
                case 'learners':
                    $isFiltered = true;
                    $user = User::find($viaResourceId);
                    $service = new BackpackAssertion($user);
                    $assertions = $service->all();
                    break;
                case 'learnerSlug':
                    $isFiltered = true;
                    $user = User::where('slug', $viaResourceId)->firstOrFail();
                    $service = new BackpackAssertion($user);
                    $assertions = $service->all();
                    break;
                case 'learnerEmail':
                    $isFiltered = true;
                    $user = User::where('email', $viaResourceId)->firstOrFail();
                    $service = new BackpackAssertion($user);
                    $assertions = $service->all();
                    break;
            }
        }

        if (!$isFiltered) {
            return [];
        }

        if ($assertions) {
            foreach ($assertions as $i => $assertion) {
                unset($assertions[$i]['entityType']);
                unset($assertions[$i]['extensions:recipientProfile']);
                $assertions[$i]['recipient_email'] = $assertions[$i]['recipient']['plaintextIdentity'];
                unset($assertions[$i]['recipient']);
                if (isset($user)) {
                    $recipient = $user;
                } else {
                    $recipient = User::where('email', '=', $assertions[$i]['recipient_email'])->first();
                }
                $assertions[$i]['recipient_id'] = $recipient->id ?? null;

                $assertions[$i]['evidenceUrl'] = isset($assertion['evidence'][0]['url']) ? $assertions[$i]['evidence'][0]['url'] : null;
                $assertions[$i]['evidenceNarrative'] = isset($assertion['evidence'][0]['narrative']) ? $assertions[$i]['evidence'][0]['narrative'] : null;

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

    public function portfolio()
    {        
        if (class_exists(\App\Models\PortfolioBadge::class)) {
            return $this->hasOne(\App\Models\PortfolioBadge::class, 'assertion_id', 'entityId');
        }

        return null;
    }
}
