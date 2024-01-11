<?php

namespace Ctrlweb\BadgeFactor2\Http\Controllers\Api;

use Ctrlweb\BadgeFactor2\Http\Controllers\Controller;
use Ctrlweb\BadgeFactor2\Http\Resources\SettingsResource;
use Ctrlweb\BadgeFactor2\Models\Setting;

/**
 * @tags Paramètres
 */
class SettingsController extends Controller
{
    public function __invoke()
    {
        return SettingsResource::make(Setting::first());
    }
}
