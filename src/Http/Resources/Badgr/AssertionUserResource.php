<?php

namespace Ctrlweb\BadgeFactor2\Http\Resources\Badgr;

use App\Http\Resources\LearnerPublicResource;
use Ctrlweb\BadgeFactor2\Models\User;
use Illuminate\Http\Resources\Json\JsonResource;

class AssertioUserResource extends JsonResource
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
            'assertion_id' => $this['assertion_id'],
            'user_id'      => $this['user_id'],
            'is_visible'   => $this['is_visible'],
        ];
    }
}
