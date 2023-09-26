<?php

namespace Ctrlweb\BadgeFactor2\Console\Commands;

use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\ArticleTag;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateWordPressPosts extends Command
{
    use CanImportWordPressCategories;
    use CanImportWordPressImages;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bf2:migrate-wp-posts';

    private $wpdb;

    private $prefix;

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import blog posts from Wordpress';

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
        $this->importCategory(ArticleCategory::class, 'category');
        $this->importCategory(ArticleTag::class, 'post_tag');
        $this->importPosts();

        $this->newLine();
        $this->line('All done!');
    }

    private function importPosts()
    {
        $this->newLine();
        $this->line('Importing posts...');

        DB::transaction(function () {
            $this->withProgressBar(
                $this->wpdb
                    ->table("{$this->prefix}posts")
                    ->select('*')
                    ->where("{$this->prefix}posts.post_type", 'post')
                    ->where("{$this->prefix}posts.post_status", 'publish')
                    ->get(),
                function ($wpPost) {
                    $postMeta = collect(
                        $this->wpdb
                            ->table("{$this->prefix}postmeta")
                            ->select('*')
                            ->where('post_id', $wpPost->ID)
                            ->get()
                    );

                    $postUser = User::where('wp_id', $wpPost->post_author)->first();

                    $category = $this->wpdb
                        ->table("{$this->prefix}term_relationships")
                        ->select("{$this->prefix}term_taxonomy.term_id", "{$this->prefix}terms.name", "{$this->prefix}terms.slug", "{$this->prefix}term_taxonomy.description")
                        ->leftJoin("{$this->prefix}term_taxonomy", "{$this->prefix}term_relationships.term_taxonomy_id", '=', "{$this->prefix}term_taxonomy.term_taxonomy_id")
                        ->leftJoin("{$this->prefix}terms", "{$this->prefix}terms.term_id", '=', "{$this->prefix}term_taxonomy.term_taxonomy_id")
                        ->where("{$this->prefix}term_taxonomy.taxonomy", 'category')
                        ->where("{$this->prefix}term_relationships.object_id", $wpPost->ID)
                        ->groupBy("{$this->prefix}term_taxonomy.term_id")
                        ->orderBy("{$this->prefix}terms.name")
                        ->first();

                    $locale = app()->currentLocale();

                    $article = Article::updateOrCreate(
                        [
                            "slug->{$locale}" => $wpPost->post_name,
                        ],
                        [
                            'title'               => $wpPost->post_title,
                            'slug'                => $wpPost->post_name,
                            'content'             => $wpPost->post_content,
                            'status'              => 'PUBLISHED',
                            'user_id'             => $postUser ? $postUser->id : null,
                            'article_category_id' => $this->ids['category'][$category->term_id],
                            'publication_date'    => $wpPost->post_date,
                        ]
                    );

                    $thumbnailId = $postMeta->firstWhere('meta_key', '_thumbnail_id') ? $postMeta->firstWhere('meta_key', '_thumbnail_id')->meta_value : null;
                    $this->importImage(Article::class, $article->id, $thumbnailId);

                    $postTags = $this->wpdb
                        ->table("{$this->prefix}term_relationships")
                        ->select("{$this->prefix}term_taxonomy.term_id", "{$this->prefix}terms.name", "{$this->prefix}terms.slug", "{$this->prefix}term_taxonomy.description")
                        ->leftJoin("{$this->prefix}term_taxonomy", "{$this->prefix}term_relationships.term_taxonomy_id", '=', "{$this->prefix}term_taxonomy.term_taxonomy_id")
                        ->leftJoin("{$this->prefix}terms", "{$this->prefix}terms.term_id", '=', "{$this->prefix}term_taxonomy.term_taxonomy_id")
                        ->where("{$this->prefix}term_taxonomy.taxonomy", 'post_tag')
                        ->where("{$this->prefix}term_relationships.object_id", $wpPost->ID)
                        ->groupBy("{$this->prefix}term_taxonomy.term_id")
                        ->orderBy("{$this->prefix}terms.name")
                        ->get();

                    foreach ($postTags as $postTag) {
                        $article->articleTags()->syncWithoutDetaching($this->ids['post_tag'][$postTag->term_id]);
                    }
                }
            );
        });
    }
}
