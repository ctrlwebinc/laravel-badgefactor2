<?php

namespace Ctrlweb\BadgeFactor2\Console\Commands;

use Illuminate\Support\Facades\DB;

trait CanImportWordPressCategories
{
    protected $ids;

    /**
     * Import Categories (Taxonomies) from WordPress to Laravel.
     *
     * @param
     * @param string $slug     Taxonomy.
     * @param string $category Category.
     *
     * @return void
     */
    protected function importCategory(string $categoryClass, string $slug)
    {
        if (!class_exists($categoryClass)) {
            throw new \ErrorException('Category class does not exist!');
        }

        $this->newLine();
        $this->line("Importing {$categoryClass}...");

        $this->ids[$slug] = [];

        DB::transaction(function () use ($categoryClass, $slug) {
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
                function ($wpCategory) use ($categoryClass, $slug) {
                    $locale = app()->currentLocale();

                    $dataUpdate = ["slug->{$locale}" => $wpCategory->slug];
                    $dataCreate = [
                        'slug'        => $wpCategory->slug,
                        'title'       => $wpCategory->name,
                        'description' => $wpCategory->description,
                    ];

                    $category = $categoryClass::updateOrCreate($dataUpdate, $dataCreate);

                    $categoryImageUrl = $this->wpdb
                        ->table("{$this->prefix}options")
                        ->select('option_value')
                        ->where('option_name', '=', 'z_taxonomy_image'.$wpCategory->term_id)
                        ->first();

                    $categoryImageUrl = isset($categoryImageUrl->option_value) ? parse_url($categoryImageUrl->option_value) : null;

                    if (! empty($categoryImageUrl['path'])) {
                        $wpAttachedFile = str_replace('/wp-content/uploads/', '', $categoryImageUrl['path']);
                        $attachment = $this->wpdb
                            ->table("{$this->prefix}posts")
                            ->join("{$this->prefix}postmeta", "{$this->prefix}posts.ID", '=', "{$this->prefix}postmeta.post_id")
                            ->select("{$this->prefix}posts.*")
                            ->where('meta_key', '=', '_wp_attached_file')
                            ->where('meta_value', '=', $wpAttachedFile)
                            ->first();
                        if ($attachment) {
                            $media = $this->importImage($categoryClass, $category->id, $attachment->ID);
                        }
                    }

                    $this->ids[$slug][$wpCategory->term_id] = $category->id;
                }
            );
        });
    }
}
