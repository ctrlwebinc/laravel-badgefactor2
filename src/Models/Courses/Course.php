<?php

namespace Ctrlweb\BadgeFactor2\Models\Courses;

use App\Models\Product;
use Ctrlweb\BadgeFactor2\Models\Badges\BadgePage;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Course extends Model
{
    use HasFactory, HasTranslations;

    protected $translatable = [
        'title',
    ];

    protected $fillable = [
        'title',
        'type',
        'duration',
        'url',
        'autoevaluation_form_url',
        'badge_page_id',
        'course_category_id',
    ];

    protected $with = ['badgePage'];

    public function generateCourseLink() {
        $url = $this->url;
    }

    public function courseCategory()
    {
        return $this->belongsTo(CourseCategory::class);
    }

    public function courseGroup()
    {
        return $this->belongsTo(CourseGroup::class);
    }

    public function badgePage() {
        return $this->belongsTo(BadgePage::class);
    }

    public function product() {
        return $this->belongsTo(Product::class);
    }
}
