<?php

namespace Ctrlweb\BadgeFactor2\Http\Controllers\Api\Badgr;

use Ctrlweb\BadgeFactor2\Http\Controllers\Controller;
use Ctrlweb\BadgeFactor2\Services\Badgr\Issuer;

/**
 * @tags Emetteurs
 */
class IssuerController extends Controller
{
    public function index()
    {
        $issuers = app(Issuer::class)->all();

        return response()->json($issuers);
    }

    public function show(string $locale, string $entityId)
    {
        $issuer = app(Issuer::class)->getBySlug($entityId);

        return response()->json($issuer);
    }

    public function count()
    {
        return app(Issuer::class)->count();
    }
}
