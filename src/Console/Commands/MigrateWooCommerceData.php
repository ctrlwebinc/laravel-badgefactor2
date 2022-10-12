<?php

namespace Ctrlweb\BadgeFactor2\Console\Commands;

use Ctrlweb\BadgeFactor2\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateWooCommerceData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bf2:migrate-wc-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrates WooCommerce data from a WordPress site to Laravel.';

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

        $this->info('Migrating WooCommerce data...');

        // Identify WooCommerce orders.
        $users = $this->withProgressBar(
            DB::connection($wordpressDb)
            ->select(
                "SELECT
                    p.ID AS order_id,
                    p.post_date,
                    max(
                        CASE WHEN pm.meta_key = '_customer_user'
                        AND p.ID = pm.post_id
                        THEN pm.meta_value
                        END
                    ) AS customer_user_id,
                    max(
                        CASE WHEN pm.meta_key = '_billing_email'
                        AND p.ID = pm.post_id
                        THEN pm.meta_value
                        END
                    ) AS billing_email,
                    max(
                        CASE WHEN pm.meta_key = '_billing_first_name'
                        AND p.ID = pm.post_id
                        THEN pm.meta_value
                        END
                    ) AS billing_first_name,
                    max(
                        CASE WHEN pm.meta_key = '_billing_last_name'
                        AND p.ID = pm.post_id
                        THEN pm.meta_value
                        END
                    ) AS billing_last_name,
                    max(
                        CASE WHEN pm.meta_key = '_billing_address_1'
                        AND p.ID = pm.post_id
                        THEN pm.meta_value
                        END
                    ) AS billing_address_1,
                    max(
                        CASE WHEN pm.meta_key = '_billing_address_2'
                        AND p.ID = pm.post_id
                        THEN pm.meta_value
                        END
                    ) AS billing_address_2,
                    max(
                        CASE WHEN pm.meta_key = '_billing_city'
                        AND p.ID = pm.post_id
                        THEN pm.meta_value
                        END
                    ) AS billing_city,
                    max(
                        CASE WHEN pm.meta_key = '_billing_state'
                        AND p.ID = pm.post_id
                        THEN pm.meta_value
                        END
                    ) AS billing_state,
                    max(
                        CASE WHEN pm.meta_key = '_billing_postcode'
                        AND p.ID = pm.post_id
                        THEN pm.meta_value
                        END
                    ) AS billing_postcode,
                    max(
                        CASE WHEN pm.meta_key = '_shipping_first_name'
                        AND p.ID = pm.post_id
                        THEN pm.meta_value
                        END
                    ) AS shipping_first_name,
                    max(
                        CASE WHEN pm.meta_key = '_shipping_last_name'
                        AND p.ID = pm.post_id
                        THEN pm.meta_value
                        END
                    ) AS shipping_last_name,
                    max(
                        CASE WHEN pm.meta_key = '_shipping_address_1'
                        AND p.ID = pm.post_id
                        THEN pm.meta_value
                        END
                    ) AS shipping_address_1,
                    max(
                        CASE WHEN pm.meta_key = '_shipping_address_2'
                        AND p.ID = pm.post_id
                        THEN pm.meta_value
                        END
                    ) AS shipping_address_2,
                    max(
                        CASE WHEN pm.meta_key = '_shipping_city'
                        AND p.ID = pm.post_id
                        THEN pm.meta_value
                        END
                    ) AS shipping_city,
                    max(
                        CASE WHEN pm.meta_key = '_shipping_state'
                        AND p.ID = pm.post_id
                        THEN pm.meta_value
                        END
                    ) AS shipping_state,
                    max(
                        CASE WHEN pm.meta_key = '_shipping_postcode'
                        AND p.ID = pm.post_id
                        THEN pm.meta_value
                        END
                    ) AS shipping_postcode,
                    max(
                        CASE WHEN pm.meta_key = '_order_total'
                        AND p.ID = pm.post_id
                        THEN pm.meta_value
                        END
                    ) AS order_total,
                    max(
                        CASE WHEN pm.meta_key = '_order_tax'
                        AND p.ID = pm.post_id
                        THEN pm.meta_value
                        END
                    ) AS order_tax,
                    max(
                        CASE WHEN pm.meta_key = '_paid_date'
                        AND p.ID = pm.post_id
                        THEN pm.meta_value
                        END
                    ) AS paid_date
            FROM
                {$prefix}posts p
                JOIN {$prefix}postmeta pm ON p.ID = pm.post_id
                JOIN {$prefix}woocommerce_order_items oi ON p.ID = oi.order_id
            WHERE
                post_type = 'shop_order'
                AND post_status = 'wc-completed'
            GROUP BY
                p.ID, p.post_date"
            ), function ($wcOrder) use ($wordpressDb, $prefix) {
                if ($wcOrder->order_total == 0.00) {
                    // Order is not imported; give user role "client" instead.
                    $user = User::where('wp_id', '=', $wpOrder->customer_user_id);
                    if ($user) {
                        $userRole = UserRole::firstOrCreate(
                            [
                                'user' => $wcOrder->customer_user_id,
                                'role' => 'client',
                            ]
                        );
                    }
                }
                $wcOrderItems = collect(
                    DB::connection($wordpressDb)
                        ->select(
                            "SELECT *
                                FROM {$prefix}woocommerce_order_items
                                WHERE order_id = ?",
                            [$wcOrder->order_id]
                        )
                );
                dd($wcOrderItems);
            }
        );

        $this->info("\nAll done!");
    }
}
