<?php

namespace Ctrlweb\BadgeFactor2\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SettingsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'address' => $this->address,
            'copyright' => $this->copyright,
            'email' => $this->email,
            'facebook' => $this->facebook,
            'phone' => $this->phone,
            'phone_2' => $this->phone_2,
            'privacy_header' => $this->privacy_header,
            'privacy_content' => $this->privacy_content,
            'register_email_confirmation_header' => $this->register_email_confirmation_header,
            'register_email_confirmation_content' => $this->register_email_confirmation_content,
            'register_page_content' => $this->register_page_content,
            'terms_header' => $this->terms_header,
            'terms_content' => $this->terms_content,
            'twitter' => $this->twitter,
            'website_name' => $this->website_name,
            'website_slogan' => $this->website_slogan,
            'logo' => $this->getMedia('logo')->first(),
            'alternative_logo' => $this->getMedia('alternative_logo')->first(),
            'members_slug' => $this->members_slug,
            'badges_slug' => $this->badges_slug,
        ];
    }
}
