<?php

namespace Ctrlweb\BadgeFactor2\Console\Commands;

use Carbon\Carbon;
use Ctrlweb\BadgeFactor2\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

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
    protected $description = 'Migrates courses from a WordPress site to Laravel.';

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

        $this->info('Migrating courses...');

        // Identify WP learners.
        $courses = $this->withProgressBar(
            DB::connection($wordpressDb)
            ->select(
                "SELECT DISTINCT u.*
                FROM {$prefix}users u
                WHERE u.user_status = 0"
            ),
            function ($wpUser) use ($wordpressDb, $prefix) {
                $userMeta = collect(
                    DB::connection($wordpressDb)
                        ->select(
                            "SELECT *
                                FROM {$prefix}usermeta
                                WHERE user_id = ?",
                            [$wpUser->ID]
                        )
                );

                // Create user.
                $user = User::updateOrCreate(
                    [
                        'email' => $wpUser->user_email,
                    ],
                    [
                        'name'        => $wpUser->display_name,
                        'first_name'  => $userMeta->firstWhere('meta_key', 'first_name')->meta_value,
                        'last_name'   => $userMeta->firstWhere('meta_key', 'last_name')->meta_value,
                        'description' => $userMeta->firstWhere('meta_key', 'description')->meta_value,
                        'website'     => '',
                        'slug'        => $wpUser->user_nicename,
                        'password'    => Hash::make($wpUser->user_pass),
                        'created_at'  => Carbon::parse($wpUser->user_registered)
                            ->setTimeZone(config('app.timezone'))
                            ->toDateTimeString(),
                        'wp_id'       => $wpUser->ID,
                        'wp_password' => $wpUser->user_pass,
                    ]
                );

                // Identify and transfer WordPress capabilities.
                if ($userMeta->firstwhere('meta_key', 'wp_capabilities')->meta_value) {
                    $capabilities = \unserialize($userMeta->firstwhere('meta_key', 'wp_capabilities')->meta_value);
                    if (array_key_exists('administrator', $capabilities) && $capabilities['administrator'] === true) {
                        $user->roles()->updateOrCreate(['role' => 'admin']);
                    }
                    if (array_key_exists('customer', $capabilities) && $capabilities['customer'] === true) {
                        $wcOrders = DB::connection($wordpressDb)
                            ->select(
                                "SELECT p.* from {$prefix}posts p
                                JOIN {$prefix}postmeta pm
                                ON p.ID = pm.post_id
                                WHERE post_type = 'shop_order'
                                AND meta_key = '_customer_user'
                                AND meta_value = '{$user->wp_id}'"
                            );

                        foreach ($wcOrders as $wcOrder) {
                            $wcOrderMeta = collect(
                                DB::connection($wordpressDb)
                                    ->select(
                                        "SELECT *
                                            FROM {$prefix}postmeta
                                            WHERE post_id = ?",
                                        [$wcOrder->ID]
                                    )
                            );

                            if (0 === intval($wcOrderMeta->firstWhere('meta_key', '_order_total')->meta_value)) {
                                // Free access: give learner-free role.
                                $user->roles()->updateOrCreate(['role' => 'learner-free']);
                            } else {
                                // Give access to specific courses.
                            }
                        }
                    }
                }
            }
        );

        $this->info("\nAll done!");
    }
}
