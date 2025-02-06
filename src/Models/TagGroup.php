<?php

namespace Ctrlweb\BadgeFactor2\Models;

use Ctrlweb\BadgeFactor2\Models\Tag;
use Illuminate\Database\Eloquent\Model;
use Ctrlweb\BadgeFactor2\Services\CacheService;
use App\Helpers\CacheHelper;

class TagGroup extends Model
{
    protected $fillable = [ "name"];

    public function tags(){
        return $this->hasMany(Tag::class);
    }

    protected static function boot()
    {
        parent::boot();

        $caches = ['tag_groups'];        

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
    
}
