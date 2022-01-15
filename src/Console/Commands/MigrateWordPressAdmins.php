<?php

namespace Ctrlweb\BadgeFactor2\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class MigrateWordPressAdmins extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bf2:migrate-wp-admins';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrates administrators from a WordPress site to Laravel.';

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
        $wordpressDb = config('badgefactor2.wordpress.connection');
        $prefix = config('badgefactor2.wordpress.db_prefix');

        // Identify WP learners.
        $users = DB::connection($wordpressDb)
            ->select(
                "SELECT DISTINCT u.*
                FROM {$prefix}users u
                LEFT JOIN {$prefix}usermeta um
                ON u.ID = um.user_id
                WHERE um.meta_key = 'wp_capabilities'
                AND um.meta_value LIKE ?
                AND u.user_status = 0",
                ['%s:13:"administrator"%']
            );
        foreach ($users as $wpUser) {
            $usermeta = collect(
                DB::connection($wordpressDb)
                    ->select(
                        "SELECT *
                            FROM {$prefix}usermeta
                            WHERE user_id = ?",
                        [$wpUser->ID]
                    )
            );

            $user = User::updateOrCreate(
                [
                    'email' => $wpUser->user_email,
                ],
                [
                    'name'        => $wpUser->user_nicename,
                    'password'    => Hash::make($wpUser->user_pass),
                    'created_at'  => $wpUser->user_registered,
                    'wp_id'       => $wpUser->ID,
                    'wp_password' => $wpUser->user_pass,
                ]
            );
        }

        return 0;
    }
}
