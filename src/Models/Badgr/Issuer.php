<?php

namespace Ctrlweb\BadgeFactor2\Models\Badgr;

use Ctrlweb\BadgeFactor2\Services\Badgr\Issuer as BadgrIssuer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
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
            $service = app(BadgrIssuer::class);

            $issuerId = $service->add(
                $issuer->name,
                $issuer->email,
                $issuer->url,
                $issuer->description ?? '',
                $issuer->image
            );

            if (!$issuerId) {
                // Renvoyer true ici laisserait croire à une réussite : on
                // repartirait avec un émetteur qui n'existe nulle part, et le
                // badge créé ensuite se rattacherait au vide. Échouer en
                // portant la raison donnée par l'API.
                throw ValidationException::withMessages([
                    'name' => $service->getLastError()
                        ?? "La création de l'organisation dans Badgr a échoué.",
                ]);
            }

            // Badgr attribue l'entityId, qui est la clé primaire de ce modèle.
            // Sans cette écriture, l'émetteur enregistré garde une clé nulle :
            // l'appelant ne peut plus désigner ce qu'il vient de créer, et le
            // badge qu'on y rattache viserait un identifiant vide.
            $issuer->entityId = $issuerId;

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
