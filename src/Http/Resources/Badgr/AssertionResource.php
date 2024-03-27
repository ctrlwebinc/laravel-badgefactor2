<?php

namespace Ctrlweb\BadgeFactor2\Http\Resources\Badgr;

use App\Http\Resources\LearnerPublicResource;
use Ctrlweb\BadgeFactor2\Models\User;
use Illuminate\Http\Resources\Json\JsonResource;

class AssertionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id'   => $this['entityId'],
            'issued_on' => $this['issuedOn'],
            'badgeclass' => $this['badgeclass'],
            'issuer' => $this['issuer'],
            'image' => $this['image'],
            'recipient' => LearnerPublicResource::make(User::where('email', '=', $this['recipient']['plaintextIdentity'])->first()),
        ];
    }
}
