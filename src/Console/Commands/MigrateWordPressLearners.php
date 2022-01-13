<?php

namespace Ctrlweb\BadgeFactor2\Console\Commands;

use Illuminate\Console\Command;

class MigrateWordPressLearners extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bf2:migrate-wp-learners';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrates learners from a WordPress site to Laravel.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        return 0;
    }
}
