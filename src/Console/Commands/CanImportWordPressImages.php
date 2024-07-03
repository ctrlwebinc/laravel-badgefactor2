<?php

namespace Ctrlweb\BadgeFactor2\Console\Commands;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Intervention\Image\Exception\NotReadableException;
use Intervention\Image\ImageManagerStatic as Image;

trait CanImportWordPressImages
{
    /**
     * Import an image from an URL into a Nova Gallery Media object.
     *
     * @param string $imageUrl
     *
     * @return bool
     */
    private function importImage(string $modelType, int $modelId, ?int $imageId, string $collectionName = 'image'): bool
    {
        if (!$imageId) {
            return false;
        }

        $wordpressDb = config('badgefactor2.wordpress.connection');
        $wpdb = DB::connection($wordpressDb);
        $media = [];

        $siteUrl = $wpdb
            ->table("{$this->prefix}options")
            ->select('option_value')
            ->where('option_name', '=', 'siteurl')
            ->first()->option_value;

        $basePath = $siteUrl.'/wp-content/uploads/';

        $image = $wpdb
            ->table("{$this->prefix}posts")
            ->select('*')
            ->where('ID', '=', $imageId)
            ->first();

        if ($image) {
            $imageMeta = $wpdb
                ->table("{$this->prefix}postmeta")
                ->select('*')
                ->where('post_id', '=', $imageId)
                ->get();

            $wpImageUrl = $basePath.$imageMeta->where('meta_key', '_wp_attached_file')->pluck('meta_value')->first();
            $wpImageAlt = $imageMeta->where('meta_key', '_wp_attachment_image_alt')->pluck('meta_value')->first();

            try {
                if (config('badgefactor2.wordpress.htaccess.user')) {
                    $wpImageFile = Http::withBasicAuth(config('badgefactor2.wordpress.htaccess.user'), config('badgefactor2.wordpress.htaccess.password'))
                        ->get($wpImageUrl);
                } else {
                    $wpImageFile = Http::get($wpImageUrl);
                }

                if ($wpImageFile->successful()) {
                    try {
                        $image = Image::make($wpImageFile->body());
                        $modelInstance = (new $modelType())->find($modelId);
                        $exists = $modelInstance->getFirstMedia();
                        if (!$exists) {
                            $modelInstance->addMediaFromBase64($image->encode('data-url'))
                            ->withCustomProperties([
                                'alt' => $wpImageAlt,
                            ])
                            ->toMediaCollection($collectionName);
                        }
                    } catch (NotReadableException $e) {
                        return false;
                    }
                }
            } catch (ConnectionException $e) {
            }
        }

        return true;
    }
}
