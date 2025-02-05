<?php

namespace Ctrlweb\BadgeFactor2\Models\Badgr;

use Ctrlweb\BadgeFactor2\Services\Badgr\Issuer as BadgrIssuer;
use Illuminate\Database\Eloquent\Model;
use App\Helpers\CacheHelper;

class Issuer extends Model
{
    use \Sushi\Sushi;

    protected $primaryKey = 'entityId';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $schema = [
        'entityId'    => 'string',
        'name'        => 'string',
        'email'       => 'string',
        'url'         => 'string',
        'description' => 'string',
        'image'       => 'string',
    ];

    protected static function booted(): void
    {
        static::creating(function (Issuer $issuer) {
            app(BadgrIssuer::class)->add(
                $issuer->name,
                $issuer->email,
                $issuer->url,
                $issuer->description ?? '',
                $issuer->image
            );            

            return true;
        });

        static::updating(function (Issuer $issuer) {
            app(BadgrIssuer::class)->update(
                $issuer->entityId,
                $issuer->name,
                $issuer->email,
                $issuer->url,
                $issuer->description ?? '',
                $issuer->image
            );


            return true;
        });

        static::deleting(function (Issuer $issuer) {
            app(BadgrIssuer::class)->delete(
                $issuer->entityId
            );

            return true;
        });

        static::saving(function (Issuer $issuer) {
            return true;
        });

        $caches = ['badge_category_certification'];        

        foreach ($caches as $key => $cache) {

            static::saved(function () use ($cache) {
                CacheHelper::forgetGroup($cache);
            });
    
            static::updated(function () use ($cache) {
                CacheHelper::forgetGroup($cache);
            });
        
            static::deleted(function () use ($cache) {
                CacheHelper::forgetGroup($cache);
            });
        }

    }

    public function getRows()
    {
        $issuers = collect(app(BadgrIssuer::class)->all())->map(function ($row) {
            return collect($row)->except(['staff', 'extensions'])->toArray();
        });

        return $issuers->all();
    }

    public function assertions()
    {
        return $this->hasMany(Assertion::class, 'issuer_id');
    }

    public function badges()
    {
        return $this->hasMany(Badge::class, 'issuer', 'entityId');
    }
}
