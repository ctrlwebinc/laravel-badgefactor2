<?php

namespace Ctrlweb\BadgeFactor2\Models;

use Ctrlweb\BadgeFactor2\Models\Tag;
use Illuminate\Database\Eloquent\Model;

class TagGroup extends Model
{
    protected $fillable = [ "name"];

    public function tags(){
        return $this->hasMany(Tag::class);
    }
}
