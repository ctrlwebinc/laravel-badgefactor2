<?php

namespace Ctrlweb\BadgeFactor2\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Ctrlweb\BadgeFactor2\Interfaces\TokenRepositoryInterface;
use League\OAuth2\Client\Token\AccessTokenInterface;
use PHP_CodeSniffer\Util\Tokens;
use Carbon\Carbon;

class BadgrConfig extends Model implements TokenRepositoryInterface
{
    protected $fillable = [
        'badgr_server_base_url',
        'client_id',
        'client_secret',
        'password_client_id',
        'password_client_secret',
        'token_set',
    ];

    protected $casts = [
    ];

    public function getTokenSet() : ?AccessTokenInterface
    {
        $tokenSet = unserialize($this->token_set);
        if (!$tokenSet)
        {
            return null;
        }
        return $tokenSet;
    }

    public function saveTokenSet(AccessTokenInterface $tokenSet)
    {
        $this->refresh();
        $this->token_set = serialize($tokenSet);
        $this->save();
    }

    public function getTokenExpiryAttribute()
    {
        $tokenSet = $this->getTokenSet();
        if (null!==$tokenSet)
        {
            $expiryTimestamp = $tokenSet->getExpires();
            if(null!==$expiryTimestamp)
            {
                return Carbon::createFromTimestamp($expiryTimestamp);
            }
        }
        return null;
    }
}
