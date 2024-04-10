<?php

namespace Ctrlweb\BadgeFactor2\Http\Resources\Badgr;

use App\Http\Resources\LearnerPublicResource;
use Ctrlweb\BadgeFactor2\Models\Badgr\AssertionUser;
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
        $recipient = LearnerPublicResource::make(User::where('email', '=', $this['recipient']['plaintextIdentity'])->first());
        $visibility = AssertionUser::where('assertion_id', '=', $this['entityId'])->where('user_id', '=', $recipient->id)->first();
        if (!$visibility || $visibility->is_visible) {
            return [
                'id'         => $this['entityId'],
                'issued_on'  => $this['issuedOn'],
                'badgeclass' => $this['badgeclass'],
                'issuer'     => $this['issuer'],
                'image'      => $this['image'],
                'recipient'  => LearnerPublicResource::make(User::where('email', '=', $this['recipient']['plaintextIdentity'])->first()),
            ];
        }
        return [
            'id'         => $this['entityId'],
            'issued_on'  => $this['issuedOn'],
            'badgeclass' => $this['badgeclass'],
            'issuer'     => $this['issuer'],
            'image'      => $this['image'],
            'recipient'  => [
                'slug' => null,
                'username' => null,
                'first_name' => 'Utilisateur',
                'last_name' => 'Anonyme',
                'description' => null,
                'website' => null,
                'establishment' => null,
                'place' => null,
                'organisation' => null,
                'job' => null,
                'biography' => null,
                'facebook' => null,
                'twitter' => null,
                'linkedin' => null,
                'photo' => null,
                'last_connexion' => null,
            ]
        ];
    }
}
