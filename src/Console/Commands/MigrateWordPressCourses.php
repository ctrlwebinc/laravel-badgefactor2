<?php

namespace Ctrlweb\BadgeFactor2\Console\Commands;

use Carbon\Carbon;
use Ctrlweb\BadgeFactor2\Models\User;
use Ctrlweb\BadgeFactor2\Models\Courses\CourseCategory;
use Ctrlweb\BadgeFactor2\Models\Courses\CourseGroup;
use Ctrlweb\BadgeFactor2\Models\Courses\CourseGroupCategory;
use Ctrlweb\BadgeFactor2\Models\Courses\Responsible;
use Ctrlweb\NovaGallery\Models\NovaGalleryMedia;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Exception\NotReadableException;
use Intervention\Image\ImageManagerStatic as Image;

class MigrateWordPressCourses extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bf2:migrate-wp-courses';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrates courses and badge pages from a WordPress site to Laravel.';

    private $wpdb;

    private $prefix;

    private $ids;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $wordpressDb = config('badgefactor2.wordpress.connection');
        $this->wpdb = DB::connection($wordpressDb);
        $this->prefix = config('badgefactor2.wordpress.db_prefix');
        $this->ids = [
            'course-category' => [],
            'course_group_categories' => [],
            'responsibles' => [],
        ];
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->importBadgePages();

        $categories = [
            'course-category' => 'course categories',
            'course_group_categories' => 'course group categories',
        ];

        foreach ($categories as $slug => $category) {
            $this->importCategory($slug, $category);
        }

        $this->importResponsibles();
        $this->importCourseGroups();
        $this->importCourses();

        $this->line("All done!");
    }

    /**
     * Import Badge Pages from WordPress to Laravel.
     *
     * @return void
     */
    protected function importBadgePages()
    {
        $this->newLine();
        $this->line('Importing badge pages...');

        DB::transaction(function () {
            $this->withProgressBar(
                $this->wpdb
                    ->table("{$this->prefix}posts")
                    ->select('*')
                    ->where('post_type', 'badge-page')
                    ->get(),
                function ($wpBadgePage) {
                    $badgePageMeta = collect(
                        $this->wpdb
                            ->table("{$this->prefix}postmeta")
                            ->select('*')
                            ->where('post_id', $wpBadgePage->ID)
                            ->get()
                    );
                    dd($badgePageMeta);

                    $course = BadgePage::updateOrCreate(
                        [
                            'badgeclass_id' => $badgePageMeta->badge,
                        ],
                        [
                            'title' => $wpBadgePage->post_title,
                            'slug' => $wpBadgePage->post_name,
                            'content' => $wpBadgePage->post_content,
                            'criteria' => $badgePageMeta->badge_criteria,
                            'approval_type' => $badgePageMeta->badge_approval_type,
                            //'badge_category_id' => ,
                            //'badge_group_id' => ,
                            //'last_updated_at' => ,

                        ]
                    );
                }
            );
        });
    }

    /**
     * Import Categories (Taxonomies) from WordPress to Laravel.
     *
     * @param string $slug Taxonomy.
     * @param string $category Category.
     * @return void
     */
    protected function importCategory($slug, $category)
    {
        $this->newLine();
        $this->line("Importing {$category}...");

        DB::transaction(function () use ($slug) {
            $this->withProgressBar(
                $this->wpdb
                    ->table("{$this->prefix}term_relationships")
                    ->select("{$this->prefix}term_taxonomy.term_id", "{$this->prefix}terms.name", "{$this->prefix}terms.slug", "{$this->prefix}term_taxonomy.description")
                    ->leftJoin("{$this->prefix}term_taxonomy", "{$this->prefix}term_relationships.term_taxonomy_id", '=', "{$this->prefix}term_taxonomy.term_taxonomy_id")
                    ->leftJoin("{$this->prefix}terms", "{$this->prefix}terms.term_id", '=', "{$this->prefix}term_taxonomy.term_taxonomy_id")
                    ->where("{$this->prefix}term_taxonomy.taxonomy", $slug)
                    ->groupBy("{$this->prefix}term_taxonomy.term_id")
                    ->orderBy("{$this->prefix}terms.name")
                    ->get(),
                function ($wpCourseCategory) use ($slug) {

                    $courseCategoryImage = $this->wpdb
                        ->table("{$this->prefix}options")
                        ->select('option_value')
                        ->where('option_name', '=', 'z_taxonomy_image'.$wpCourseCategory->term_id)
                        ->first();

                    $novaGalleryMedia = $courseCategoryImage ?
                        $this->importImage($courseCategoryImage->option_value) :
                        $this->importImage(null);

                    $locale = app()->currentLocale();

                    switch ($slug) {
                        case 'course-category':
                            $category = CourseCategory::updateOrCreate(
                                [
                                    "slug->{$locale}" => $wpCourseCategory->slug,
                                ],
                                [
                                    'slug' => $wpCourseCategory->slug,
                                    'title' => $wpCourseCategory->name,
                                    'description' => $wpCourseCategory->description,
                                    'image' => substr($novaGalleryMedia->path, 8),
                                ]
                            );
                            $this->ids['course-category'][$wpCourseCategory->term_id] = $category->id;
                            break;
                        case 'course_group_categories':
                            $category = CourseGroupCategory::updateOrCreate(
                                [
                                    "slug->{$locale}" => $wpCourseCategory->slug,
                                ],
                                [
                                    'slug' => $wpCourseCategory->slug,
                                    'title' => $wpCourseCategory->name,
                                    'description' => $wpCourseCategory->description,
                                    'image' => substr($novaGalleryMedia->path, 8),
                                ]
                            );
                            $this->ids['course_group_categories'][$wpCourseCategory->term_id] = $category->id;
                            break;
                    }
                }
            );
        });
    }

    protected function importResponsibles()
    {
        $this->newLine();
        $this->line('Importing course groups responsibles...');

        DB::transaction(function () {
            $this->withProgressBar(
                $this->wpdb
                    ->table("{$this->prefix}posts")
                    ->where("{$this->prefix}posts.post_type", 'c21_responsable')
                    ->get(),
                function ($wpResponsible) {
                    $wpResponsibleMeta = collect(
                        $this->wpdb
                            ->select(
                                "SELECT *
                                    FROM {$this->prefix}postmeta
                                    WHERE post_id = ?",
                                [$wpResponsible->ID]
                            )
                    );

                    $locale = app()->currentLocale();

                    $wpImage = $wpResponsibleMeta->firstWhere('meta_key', 'image') ? $wpResponsibleMeta->firstWhere('meta_key', 'image')->meta_value : null;
                    $novaGalleryMedia = $this->importImage($wpImage);

                    $wpDescription = $wpResponsibleMeta->firstWhere('meta_key', 'description') ? $wpResponsibleMeta->firstWhere('meta_key', 'description')->meta_value : null;

                    $responsible = Responsible::updateOrCreate(
                        [
                            "slug->{$locale}" => $wpResponsible->post_name,
                        ],
                        [
                            'slug' => $wpResponsible->post_name,
                            'name' => $wpResponsible->post_title,
                            'description' => $wpDescription,
                            'image' => $novaGalleryMedia->path
                        ]
                    );
                    $this->ids['responsibles'][$wpResponsible->ID] = $responsible->id;
                }
            );
        });
    }

    /**
     * Import Course Groups from WordPress to Laravel.
     *
     * @return void
     */
    protected function importCourseGroups()
    {
        $this->newLine();
        $this->line('Importing course groups...');

        DB::transaction(function () {
            $this->withProgressBar(
                $this->wpdb
                    ->table("{$this->prefix}posts")
                    ->where("{$this->prefix}posts.post_type", 'c21_course_group')
                    ->get(),
                function ($wpCourseGroup) {
                    $wpCourseGroupMeta = collect(
                        $this->wpdb
                            ->select(
                                "SELECT *
                                    FROM {$this->prefix}postmeta
                                    WHERE post_id = ?",
                                [$wpCourseGroup->ID]
                            )
                    );
                    $image = $wpCourseGroupMeta->firstWhere('meta_key', 'image')->meta_value;
                    $novaGalleryMedia = $this->importImage($image);

                    $locale = app()->currentLocale();

                    $termRelationship = collect(
                        $this->wpdb
                            ->select(
                                "SELECT *
                                    FROM {$this->prefix}term_relationships
                                    WHERE object_id = ?",
                                [$wpCourseGroup->ID]
                            )
                    )->first();
                    $courseGroupCategoryId = $this->ids['course_group_categories'][$termRelationship->term_taxonomy_id];

                    $courseGroup = CourseGroup::updateOrCreate(
                        [
                            "slug->{$locale}" => $wpCourseGroup->post_name,
                        ],
                        [
                            'slug' => $wpCourseGroup->post_name,
                            'title' => $wpCourseGroup->post_title,
                            'subtitle' => $wpCourseGroupMeta->firstWhere('meta_key', 'subtitle')->meta_value,
                            'description' => $wpCourseGroupMeta->firstWhere('meta_key', 'description')->meta_value,
                            'image' => substr($novaGalleryMedia->path, 8),
                            'course_group_category_id' => $courseGroupCategoryId,
                        ]
                    );

                    foreach (unserialize($wpCourseGroupMeta->firstWhere('meta_key', 'badge_page_in_charge_of_feedback')->meta_value) as $wpResponsibleId) {
                        $courseGroup->retroactionResponsibles()->syncWithoutDetaching($this->ids['responsibles'][$wpResponsibleId]);
                    }

                    foreach (unserialize($wpCourseGroupMeta->firstWhere('meta_key', 'badge_page_experts')->meta_value) as $wpResponsibleId) {
                        $courseGroup->contentSpecialists()->syncWithoutDetaching($this->ids['responsibles'][$wpResponsibleId]);
                    }

                }
            );
        });
    }



    /**
     * Import Courses from WordPress to Laravel.
     *
     * @return void
     */
    protected function importCourses()
    {
        $this->newLine();
        $this->line('Importing courses...');

        DB::transaction(function () {
            $this->withProgressBar(
                $this->wpdb
                    ->table("{$this->prefix}posts")
                    ->select('*')
                    ->where('post_type', 'course')
                    ->get(),
                function ($wpCourse) {
                    $courseMeta = collect(
                        $this->wpdb
                            ->table("{$this->prefix}postmeta")
                            ->select('*')
                            ->where('post_id', $wpCourse->ID)
                            ->get()
                    );
                    dd($courseMeta);

                    $course = Course::updateOrCreate(
                        [
                            'url' => $wpCourse->guid,
                        ],
                        [
                            'title' => $wpCourse->post_title,
                            'duration' => $courseMeta->course_duration,
                            'price' => (int)$courseMeta->price,


                        ]
                    );
                }
            );
        });
    }

    /**
     * Import an image from an URL into a Nova Gallery Media object.
     *
     * @param string $imageUrl
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
