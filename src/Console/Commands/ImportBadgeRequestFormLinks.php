<?php

namespace Ctrlweb\BadgeFactor2\Console\Commands;

use Ctrlweb\BadgeFactor2\Models\Badges\BadgePage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportBadgeRequestFormLinks extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bf2:import-badge-request-form-links';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Imports Badge Request Form Links from WordPress to Badge Pages in Laravel.';

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
        $this->line('Importing badge request form links...');

        DB::transaction(function () {
            $this->withProgressBar(
                $this->wpdb
                    ->table("{$this->prefix}posts")
                    ->select('*')
                    ->where('post_type', 'badge-request-form')
                    ->where('post_status', 'publish')
                    ->get(),
                function ($wpBadgeRequestForm) {
                    $badgeRequestFormMeta = collect(
                        $this->wpdb
                            ->table("{$this->prefix}postmeta")
                            ->select('*')
                            ->where('post_id', $wpBadgeRequestForm->ID)
                            ->get()
                    );

                    $badgeClass = $badgeRequestFormMeta->firstWhere('meta_key', 'badge') ? $badgeRequestFormMeta->firstWhere('meta_key', 'badge')->meta_value : null;

                    if ($badgeClass) {
                        foreach (BadgePage::where('badgeclass_id', '=', $badgeClass)->get() as $badgePage) {
                            $badgePage->request_form_url = config('badgefactor2.wordpress.base_url') . '/badges/' . $wpBadgeRequestForm->post_name . '/formulaire/';
                            $badgePage->save();
                        }
                    }
                }
            );
        });


        $this->newLine();
        $this->line('All done!');
    }
}
