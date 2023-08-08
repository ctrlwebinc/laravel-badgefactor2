<?php

namespace Ctrlweb\BadgeFactor2\Console\Commands;

use Ctrlweb\NovaGallery\Models\NovaGalleryMedia;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Exception\NotReadableException;
use Intervention\Image\ImageManagerStatic as Image;

trait CanImportWordPressImages
{
    /**
     * Import an image from an URL into a Nova Gallery Media object.
     *
     * @param string $imageUrl
     *
     * @return NovaGalleryMedia|null
     */
    private function importImage($imageUrl)
    {
        $fileName = null;
        $imagePath = null;
        $novaGalleryMedia = new NovaGalleryMedia();
        if ($imageUrl) {
            try {
                $image = Image::make($imageUrl);
                $fileName = md5(substr($imageUrl, strrpos($imageUrl, '/') + 1));
                $fileName .= substr($imageUrl, strrpos($imageUrl, '.'));
                $imagePath = 'uploads/'.$fileName;
                Storage::disk('public')->put($imagePath, $image);

                $novaGalleryMedia = NovaGalleryMedia::updateOrCreate(
                    [
                        'path' => 'storage/'.$imagePath,
                    ],
                    [
                        'file_name' => $fileName,
                        'mime_type' => $image->mime(),
                    ]
                );
            } catch (NotReadableException $e) {
                return new NovaGalleryMedia();
            }
        }

        return $novaGalleryMedia;
    }
}


