<?php

namespace Ctrlweb\BadgeFactor2\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Translatable\HasTranslations;

class Setting extends Model implements HasMedia
{
    use HasFactory;
    use HasTranslations;
    use InteractsWithMedia;

    protected $fillable = [
        'address',
        'copyright',
        'email',
        'facebook',
        'phone',
        'privacy_content',
        'register_email_confirmation_content',
        'register_email_confirmation_header',
        'register_page_content',
        'terms_content',
        'terms_header',
        'twitter',
        'website_name',
        'website_slogan',
    ];

    protected $translatable = [
        'address',
        'email',
        'phone',
        'facebook',
        'twitter',
        'copyright',
        'website_name',
        'website_slogan',
        'register_page_content',
        'register_email_confirmation_header',
        'register_email_confirmation_content',
        'terms_header',
        'terms_content',
        'privacy_header',
        'privacy_content',
    ];

    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(32)
            ->height(32);
    }
}
