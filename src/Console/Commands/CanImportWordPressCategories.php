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
                    $categoryImage = $this->wpdb
                        ->table("{$this->prefix}options")
                        ->select('option_value')
                        ->where('option_name', '=', 'z_taxonomy_image'.$wpCategory->term_id)
                        ->first();

                    $novaGalleryMedia = $categoryImage ?
                        $this->importImage($categoryImage->option_value) :
                        $this->importImage(null);

                    $locale = app()->currentLocale();

                    $dataUpdate = ["slug->{$locale}" => $wpCategory->slug];
                    $dataCreate = [
                        'slug'        => $wpCategory->slug,
                        'title'       => $wpCategory->name,
                        'description' => $wpCategory->description,
                        'image'       => substr($novaGalleryMedia->path, 8),
                    ];

                    $category = $categoryClass::updateOrCreate($dataUpdate, $dataCreate);
                    $this->ids[$slug][$wpCategory->term_id] = $category->id;
                }
            );
        });
    }
}
