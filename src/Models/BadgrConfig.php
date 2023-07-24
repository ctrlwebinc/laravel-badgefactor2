<?php

namespace Ctrlweb\BadgeFactor2\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BadgrConfig extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'client_secret',
        'redirect_uri',
        'scopes',
        'access_token',
        'refresh_token',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime:Y-m-d H:i:s',
    ];

    public function getAccessTokenToArray()
    {
        return [
            'access_token'  => $this->access_token,
            'refresh_token' => $this->refresh_token,
            'expires_at'    => $this->expires_at->format('Y-m-d H:i:s')
        ];
    }
}
