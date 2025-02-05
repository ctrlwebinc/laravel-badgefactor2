<?php

namespace Ctrlweb\BadgeFactor2\Models;


use Illuminate\Database\Eloquent\Model;
use Ctrlweb\BadgeFactor2\Models\TagGroup;
use Ctrlweb\BadgeFactor2\Models\Courses\CourseGroup;
use Ctrlweb\BadgeFactor2\Services\CacheService;

class Tag extends Model
{
    protected $fillable = [ "name", "tag_group_id"];  

    protected static function boot()
    {
        parent::boot();

        CacheService::restoreCache(SELF, ['tag_groups_*']);
    
    }


    public function tagGroup(){
        return $this->belongsTo(TagGroup::class);
    }

    public function course_groups(){
        return $this->belongsToMany(CourseGroup::class, 'course_group_tags', 'tag_id', 'course_group_id');
    }
}
