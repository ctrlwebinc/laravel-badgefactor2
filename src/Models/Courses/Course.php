<?php

namespace Ctrlweb\BadgeFactor2\Models\Courses;

use Ctrlweb\BadgeFactor2\Models\Badges\BadgePage;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Course extends Model
{
    use HasFactory, HasTranslations;

    protected $translatable = [
        'title',
        'description',
    ];

    protected $fillable = [
        'title',
        'description',
        'type',
        'duration',
        'url',
        'autoevaluation_form_url',
        'badge_page_id',
        'course_category_id',
        "regular_price",
        "promo_price",
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

    public function price(): Attribute
    {
        if (!is_null($this->promo_price)) {
            $price = $this->promo_price;
        } else {
            $price = $this->regular_price;
        }
        return Attribute::make(
            get: fn() => $price
        );
    }

    public function carts(): BelongsToMany
    {
        return $this->belongsToMany(Cart::class, 'cart_product');
    }
}
