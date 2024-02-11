<?php

namespace Ctrlweb\BadgeFactor2\Console\Commands;

use Ctrlweb\BadgeFactor2\Models\Badges\BadgeCategory;
use Ctrlweb\BadgeFactor2\Models\Badges\BadgePage;
use Ctrlweb\BadgeFactor2\Models\Courses\Course;
use Ctrlweb\BadgeFactor2\Models\Courses\CourseGroup;
use Ctrlweb\BadgeFactor2\Models\Courses\CourseGroupCategory;
use Ctrlweb\BadgeFactor2\Models\Courses\Responsible;
use Ctrlweb\BadgeFactor2\Models\Courses\TargetAudience;
use Ctrlweb\BadgeFactor2\Models\Courses\TechnicalRequirement;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateWordPressCourses extends Command
{
    use CanImportWordPressCategories;
    use CanImportWordPressImages;

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
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->importCategory(BadgeCategory::class, 'badge-category');
        $this->importCategory(CourseGroupCategory::class, 'course_group_categories');
        $this->importCategory(TargetAudience::class, 'public-cible');
        $this->importCategory(TechnicalRequirement::class, 'exigence_technique_de_cours');
        $this->importBadgePages();
        $this->importResponsibles();
        $this->importCourseGroups();
        $this->importCourses();

        $this->newLine();
        $this->line('All done!');
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
                    ->where('post_status', 'publish')
                    ->get(),
                function ($wpBadgePage) {
                    $badgePageMeta = collect(
                        $this->wpdb
                            ->table("{$this->prefix}postmeta")
                            ->select('*')
                            ->where('post_id', $wpBadgePage->ID)
                            ->get()
                    );

                    $wpBadgePageCategory = $this->wpdb
                        ->table("{$this->prefix}term_relationships")
                        ->select("{$this->prefix}term_taxonomy.term_id", "{$this->prefix}terms.name", "{$this->prefix}terms.slug", "{$this->prefix}term_taxonomy.description")
                        ->leftJoin("{$this->prefix}term_taxonomy", "{$this->prefix}term_relationships.term_taxonomy_id", '=', "{$this->prefix}term_taxonomy.term_taxonomy_id")
                        ->leftJoin("{$this->prefix}terms", "{$this->prefix}terms.term_id", '=', "{$this->prefix}term_taxonomy.term_taxonomy_id")
                        ->where("{$this->prefix}term_taxonomy.taxonomy", 'badge-category')
                        ->where("{$this->prefix}term_relationships.object_id", $wpBadgePage->ID)
                        ->groupBy("{$this->prefix}term_taxonomy.term_id")
                        ->orderBy("{$this->prefix}terms.name")
                        ->first();

                    $badgePageCategoryId = isset($wpBadgePageCategory->term_id) ? $this->ids['badge-category'][$wpBadgePageCategory->term_id] : null;

                    if (isset($badgePageMeta->firstWhere('meta_key', 'badge')->meta_value)) {
                        $badgePage = BadgePage::updateOrCreate(
                            [
                                'badgeclass_id' => $badgePageMeta->firstWhere('meta_key', 'badge')->meta_value,
                            ],
                            [
                                'type'              => 'certification', // FIXME
                                'badgeclass_id'     => $badgePageMeta->firstWhere('meta_key', 'badge')->meta_value,
                                'title'             => $wpBadgePage->post_title,
                                'slug'              => $wpBadgePage->post_name,
                                'content'           => $wpBadgePage->post_content,
                                'criteria'          => $badgePageMeta->firstWhere('meta_key', 'badge_criteria') ? $badgePageMeta->firstWhere('meta_key', 'badge_criteria')->meta_value : null,
                                'approval_type'     => $badgePageMeta->firstWhere('meta_key', 'badge_approval_type') ? $badgePageMeta->firstWhere('meta_key', 'badge_approval_type')->meta_value : null,
                                'request_type'      => 'external',
                                'request_form_url'  => null, // FIXME
                                'badge_category_id' => $badgePageCategoryId,
                                'duration'          => null,
                                'video_url'         => $badgePageMeta->firstWhere('meta_key', 'badgepage_video_url') ? $badgePageMeta->firstWhere('meta_key', 'badgepage_video_url')->meta_value : null,
                                'last_updated_at'   => $badgePageMeta->firstWhere('meta_key', 'badgepage_latest_update_date') ? $badgePageMeta->firstWhere('meta_key', 'badgepage_latest_update_date')->meta_value : null,
                            ]
                        );


                        if ($badgePageMeta->firstWhere('meta_key', 'supplementary_image_id')) {
                            $this->importImage(BadgePage::class, $badgePage->id, $badgePageMeta->firstWhere('meta_key', 'supplementary_image_id')->meta_value);
                        }

                        $this->ids['badge-page'][$wpBadgePage->ID] = $badgePage->id;
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
                    ->where("{$this->prefix}posts.post_status", 'publish')
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

                    $wpDescription = $wpResponsibleMeta->firstWhere('meta_key', 'description') ? $wpResponsibleMeta->firstWhere('meta_key', 'description')->meta_value : null;

                    $responsible = Responsible::updateOrCreate(
                        [
                            "slug->{$locale}" => $wpResponsible->post_name,
                        ],
                        [
                            'slug'        => $wpResponsible->post_name,
                            'name'        => $wpResponsible->post_title,
                            'description' => $wpDescription,
                        ]
                    );

                    $wpImage = $wpResponsibleMeta->firstWhere('meta_key', 'image') ? $wpResponsibleMeta->firstWhere('meta_key', 'image')->meta_value : null;
                    $wpImageId = $wpResponsibleMeta->firstWhere('meta_key', 'image_id') ? $wpResponsibleMeta->firstWhere('meta_key', 'image_id')->meta_value : null;
                    $this->importImage(Responsible::class, $responsible->id, $wpImageId);

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
                    ->where("{$this->prefix}posts.post_status", 'publish')
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
                            'slug'                     => $wpCourseGroup->post_name,
                            'title'                    => $wpCourseGroup->post_title,
                            'subtitle'                 => $wpCourseGroupMeta->firstWhere('meta_key', 'subtitle')->meta_value,
                            'description'              => $wpCourseGroupMeta->firstWhere('meta_key', 'description')->meta_value,
                            'course_group_category_id' => $courseGroupCategoryId,
                        ]
                    );

                    $imageId = $wpCourseGroupMeta->firstWhere('meta_key', 'image_id') ? $wpCourseGroupMeta->firstWhere('meta_key', 'image_id')->meta_value : null;
                    $this->importImage(CourseGroup::class, $courseGroup->id, $imageId);

                    $this->ids['course_groups'][$wpCourseGroup->ID] = $courseGroup->id;

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

        $wpBadgeFactor2Options = $this->wpdb
            ->table("{$this->prefix}options")
            ->select('*')
            ->where('option_name', 'badgefactor2')
            ->first();
        $wpBadgeFactor2Options = unserialize($wpBadgeFactor2Options->option_value);
        $wpFormSlug = $wpBadgeFactor2Options['bf2_form_slug'];
        $wpAutoevaluationFormSlug = $wpBadgeFactor2Options['bf2_autoevaluation_form_slug'];

        DB::transaction(function () use ($wpAutoevaluationFormSlug) {
            $this->withProgressBar(
                $this->wpdb
                    ->table("{$this->prefix}posts")
                    ->select('*')
                    ->where("{$this->prefix}posts.post_type", 'course')
                    ->where("{$this->prefix}posts.post_status", 'publish')
                    ->get(),
                function ($wpCourse) use ($wpAutoevaluationFormSlug) {
                    $courseMeta = collect(
                        $this->wpdb
                            ->table("{$this->prefix}postmeta")
                            ->select('*')
                            ->where('post_id', $wpCourse->ID)
                            ->get()
                    );

                    $wpCourseLink = config('badgefactor2.wordpress.base_url').'/courses/'.$wpCourse->post_name;

                    $wpBadgePageId = isset($courseMeta->firstWhere('meta_key', 'course_badge_page')->meta_value) ? $courseMeta->firstWhere('meta_key', 'course_badge_page')->meta_value : null;

                    $wpBadgePage = $this->wpdb
                        ->table("{$this->prefix}posts")
                        ->select('*')
                        ->where('post_type', 'badge-page')
                        ->where('ID', $wpBadgePageId)
                        ->first();

                    $badgePageMeta = isset($wpBadgePage) ? collect(
                        $this->wpdb
                            ->table("{$this->prefix}postmeta")
                            ->select('*')
                            ->where('post_id', $wpBadgePage->ID)
                            ->get()
                    ) : null;

                    $autoevaluationFormId = $badgePageMeta && $badgePageMeta->firstWhere('meta_key', 'autoevaluation_form_id') ? $badgePageMeta->firstWhere('meta_key', 'autoevaluation_form_id')->meta_value : null;

                    $autoevaluationFormUrl = isset($autoevaluationFormId) && isset($wpBadgePage->guid) ? $wpBadgePage->guid.$wpAutoevaluationFormSlug : null;

                    $wpCourseGroupCategory = $this->wpdb
                        ->table("{$this->prefix}term_relationships")
                        ->select("{$this->prefix}term_taxonomy.term_id", "{$this->prefix}terms.name", "{$this->prefix}terms.slug", "{$this->prefix}term_taxonomy.description")
                        ->leftJoin("{$this->prefix}term_taxonomy", "{$this->prefix}term_relationships.term_taxonomy_id", '=', "{$this->prefix}term_taxonomy.term_taxonomy_id")
                        ->leftJoin("{$this->prefix}terms", "{$this->prefix}terms.term_id", '=', "{$this->prefix}term_taxonomy.term_taxonomy_id")
                        ->where("{$this->prefix}term_taxonomy.taxonomy", 'course-category')
                        ->where("{$this->prefix}term_relationships.object_id", $wpCourse->ID)
                        ->groupBy("{$this->prefix}term_taxonomy.term_id")
                        ->orderBy("{$this->prefix}terms.name")
                        ->first();

                    $courseGroupCategoryId = isset($wpCourseGroupCategory->term_id) ? $this->ids['course-category'][$wpCourseGroupCategory->term_id] : null;

                    $courseGroupId = $this->wpdb
                        ->table("{$this->prefix}posts")
                        ->select('ID')
                        ->leftJoin("{$this->prefix}postmeta", "{$this->prefix}posts.ID", '=', "{$this->prefix}postmeta.post_id")
                        ->where("{$this->prefix}postmeta.meta_key", 'group_courses')
                        ->where("{$this->prefix}postmeta.meta_value", 'LIKE', "%\"{$wpCourse->ID}\"%")
                        ->first();

                    $course = Course::updateOrCreate(
                        [
                            'title->fr'                   => $wpCourse->post_title, // FIXME import language properly.
                        ],
                        [
                            'duration'                => isset($courseMeta->firstWhere('meta_key', 'course_duration')->meta_value) ? $courseMeta->firstWhere('meta_key', 'course_duration')->meta_value : 0,
                            'url'                     => $wpCourseLink,
                            'autoevaluation_form_url' => $autoevaluationFormUrl,
                            'badge_page_id'           => $wpBadgePageId && isset($this->ids['badge-page'][$wpBadgePageId]) ? $this->ids['badge-page'][$wpBadgePageId] : null,
                            'title'                   => $wpCourse->post_title,
                            'decription'              => null,
                            'course_group_id'         => isset($courseGroupId->ID) ? $this->ids['course_groups'][$courseGroupId->ID] : null,
                            'regular_price'           => isset($courseMeta->firstWhere('meta_key', 'price')->meta_value) ? (int) $courseMeta->firstWhere('meta_key', 'price')->meta_value : null,
                        ]
                    );

                    $wpTargetAudiences = $this->wpdb
                        ->table("{$this->prefix}term_relationships")
                        ->select("{$this->prefix}term_taxonomy.term_id", "{$this->prefix}terms.name", "{$this->prefix}terms.slug", "{$this->prefix}term_taxonomy.description")
                        ->leftJoin("{$this->prefix}term_taxonomy", "{$this->prefix}term_relationships.term_taxonomy_id", '=', "{$this->prefix}term_taxonomy.term_taxonomy_id")
                        ->leftJoin("{$this->prefix}terms", "{$this->prefix}terms.term_id", '=', "{$this->prefix}term_taxonomy.term_taxonomy_id")
                        ->where("{$this->prefix}term_taxonomy.taxonomy", 'public-cible')
                        ->where("{$this->prefix}term_relationships.object_id", $wpBadgePageId)
                        ->groupBy("{$this->prefix}term_taxonomy.term_id")
                        ->orderBy("{$this->prefix}terms.name")
                        ->get();

                    foreach ($wpTargetAudiences as $wpTargetAudience) {
                        $course->targetAudiences()->syncWithoutDetaching([$this->ids['public-cible'][$wpTargetAudience->term_id]]);
                    }

                    $wpTechnicalRequirements = $this->wpdb
                        ->table("{$this->prefix}term_relationships")
                        ->select("{$this->prefix}term_taxonomy.term_id", "{$this->prefix}terms.name", "{$this->prefix}terms.slug", "{$this->prefix}term_taxonomy.description")
                        ->leftJoin("{$this->prefix}term_taxonomy", "{$this->prefix}term_relationships.term_taxonomy_id", '=', "{$this->prefix}term_taxonomy.term_taxonomy_id")
                        ->leftJoin("{$this->prefix}terms", "{$this->prefix}terms.term_id", '=', "{$this->prefix}term_taxonomy.term_taxonomy_id")
                        ->where("{$this->prefix}term_taxonomy.taxonomy", 'exigence_technique_de_cours')
                        ->where("{$this->prefix}term_relationships.object_id", $wpBadgePageId)
                        ->groupBy("{$this->prefix}term_taxonomy.term_id")
                        ->orderBy("{$this->prefix}terms.name")
                        ->get();

                    foreach ($wpTechnicalRequirements as $wpTechnicalRequirement) {
                        $course->technicalRequirements()->syncWithoutDetaching([$this->ids['exigence_technique_de_cours'][$wpTechnicalRequirement->term_id]]);
                    }
                }
            );
        });
    }
}
