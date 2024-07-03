<?php

namespace Ctrlweb\BadgeFactor2\Console\Commands;

use Ctrlweb\BadgeFactor2\Models\Badges\BadgeCategory;
use Ctrlweb\BadgeFactor2\Models\Badges\BadgePage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixBadgeCategories extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bf2:fix-badge-categories';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fixes the Badge Categories associated with Badge Pages, from a WordPress site to Laravel.';

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
        $this->newLine();
        $this->line('Fixing Badge Categories...');

        DB::transaction(function () {
            $this->withProgressBar(
                $this->wpdb
                    ->table("{$this->prefix}posts")
                    ->select('*')
                    ->where('post_type', 'badge-page')
                    ->where('post_status', 'publish')
                    ->get(),
                function ($wpBadgePage) {
                    $locale = config('app.locale');
                    $badgePage = BadgePage::where("slug->{$locale}", '=', $wpBadgePage->post_name)->first();

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

                    $badgeCategory = $wpBadgePageCategory ? BadgeCategory::where("slug->{$locale}", '=', $wpBadgePageCategory->slug)->first() : null;

                    if ($badgePage && $badgeCategory) {
                        $badgePage->badge_category_id = $badgeCategory->id;
                        $badgePage->save();
                    }
                }
            );
        });

        $this->newLine();
        $this->line('All done!');
    }
}
