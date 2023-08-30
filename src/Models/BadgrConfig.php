<?php

namespace Ctrlweb\BadgeFactor2\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;


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

    protected function tokens(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes) => [
                'access_token'  => $attributes['access_token'],
                'refresh_token' => $attributes['refresh_token'],
                'expires_at'    => $attributes['expires_at'],
            ],
        );
    }
}
