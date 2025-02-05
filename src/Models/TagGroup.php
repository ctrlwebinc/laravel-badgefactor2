<?php

namespace Ctrlweb\BadgeFactor2\Models;

use Ctrlweb\BadgeFactor2\Models\Tag;
use Illuminate\Database\Eloquent\Model;
use Ctrlweb\BadgeFactor2\Services\CacheService;

class TagGroup extends Model
{
    protected $fillable = [ "name"];

    public function tags(){
        return $this->hasMany(Tag::class);
    }

    protected static function boot()
    {
        parent::boot();

        CacheService::restoreCache(SELF, ['tag_groups_*']);
    }
    
}
