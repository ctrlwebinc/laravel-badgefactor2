<?php

namespace Ctrlweb\BadgeFactor2\Console\Commands;

use Carbon\Carbon;
use Ctrlweb\BadgeFactor2\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Intervention\Image\Exception\NotReadableException;
use Intervention\Image\ImageManagerStatic as Image;

class MigrateWordPressUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bf2:migrate-wp-users';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrates users from a WordPress site to Laravel.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    private function decryptWPEncryptedUserBadgrPassword(string $password): string
    {
        $encrypt_method = env('BF2_ENCRYPTION_ALGORITHM');
        $secret_key = env('BF2_SECRET_KEY');
        $secret_iv = env('BF2_SECRET_IV');

        // hash.
        $key = hash('sha256', $secret_key);

        // iv - encrypt method AES-256-CBC expects 16 bytes - else you will get a warning.
        $iv = substr(hash('sha256', $secret_iv), 0, 16);

        return openssl_decrypt($password, $encrypt_method, $key, 0, $iv);
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

        $this->info('Migrating users...');

        // Identify WP learners.
        $this->withProgressBar(
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
                $bpProfile = collect(
                    DB::connection($wordpressDb)
                        ->select(
                            "SELECT *
                                FROM {$prefix}bp_xprofile_data
                                WHERE user_id = ?",
                            [$wpUser->ID]
                        )
                );

                // Create user.
                User::withoutEvents(function () use ($wpUser, $userMeta, $bpProfile, $wordpressDb, $prefix) {
                    $user = User::updateOrCreate(
                        [
                            'email' => $wpUser->user_email,
                        ],
                        [
                            'name'             => $bpProfile->firstWhere('field_id', 1) ? $bpProfile->firstWhere('field_id', 1)->value : $wpUser->display_name,
                            'password'         => Hash::make($wpUser->user_pass),
                            'created_at'       => Carbon::parse($wpUser->user_registered)
                                ->setTimeZone(config('app.timezone'))
                                ->toDateTimeString(),
                            'updated_at'       => Carbon::parse($wpUser->user_registered)
                                ->setTimeZone(config('app.timezone'))
                                ->toDateTimeString(),
                            'first_name'       => $userMeta->firstWhere('meta_key', 'first_name')->meta_value,
                            'last_name'        => $userMeta->firstWhere('meta_key', 'last_name')->meta_value,
                            'description'      => $userMeta->firstWhere('meta_key', 'description')->meta_value,
                            'website'          => $bpProfile->firstWhere('field_id', 5) ? $bpProfile->firstWhere('field_id', 5)->value : null,
                            'slug'             => $wpUser->user_nicename ? Str::slug($wpUser->user_nicename) : Str::slug($wpUser->user_login),
                            'wp_id'            => $wpUser->ID,
                            'wp_password'      => $wpUser->user_pass,
                            'place'            => $bpProfile->firstWhere('field_id', 4) ? $bpProfile->firstWhere('field_id', 4)->value : null,
                            'organisation'     => $bpProfile->firstWhere('field_id', 2) ? $bpProfile->firstWhere('field_id', 2)->value : null,
                            'job'              => $bpProfile->firstWhere('field_id', 3) ? $bpProfile->firstWhere('field_id', 3)->value : null,
                            'biography'        => $bpProfile->firstWhere('field_id', 6) ? $bpProfile->firstWhere('field_id', 6)->value : null,
                            'facebook'         => $bpProfile->firstWhere('field_id', 7) ? $bpProfile->firstWhere('field_id', 7)->value : null,
                            'twitter'          => $bpProfile->firstWhere('field_id', 8) ? $bpProfile->firstWhere('field_id', 8)->value : null,
                            'linkedin'         => $bpProfile->firstWhere('field_id', 9) ? $bpProfile->firstWhere('field_id', 9)->value : null,
                            'user_status'      => 'ACTIVE',
                            'last_connexion'   => $userMeta->firstWhere('meta_key', 'last_activity') ?
                                Carbon::parse($userMeta->firstWhere('meta_key', 'last_activity')->meta_value)
                                    ->setTimeZone(config('app.timezone'))
                                    ->toDateTimeString() :
                                null,
                            'username'         => $wpUser->user_login,
                            'badgr_user_state' => $userMeta->firstWhere('meta_key', 'badgr_user_state') ? $userMeta->firstWhere('meta_key', 'badgr_user_state')->meta_value : null,
                            'badgr_user_slug'  => $userMeta->firstWhere('meta_key', 'badgr_user_slug') ? $userMeta->firstWhere('meta_key', 'badgr_user_slug')->meta_value : null,
                        ]
                    );

                    $user->badgr_encrypted_password = $userMeta->firstWhere('meta_key', 'badgr_password') ? $this->decryptWPEncryptedUserBadgrPassword($userMeta->firstWhere('meta_key', 'badgr_password')->meta_value) : null;
                    $user->save();

                    // Identify and transfer WordPress capabilities.
                    if ($userMeta->firstwhere('meta_key', 'wp_capabilities')->meta_value) {
                        $capabilities = \unserialize($userMeta->firstwhere('meta_key', 'wp_capabilities')->meta_value);

                        if (array_key_exists('administrator', $capabilities) && $capabilities['administrator'] === true) {
                            $user->assignRole(User::ADMINISTRATOR);
                        } elseif (array_key_exists('approver', $capabilities) && $capabilities['approver'] === true) {
                            $user->assignRole(User::APPROVER);
                        } else {
                            $user->assignRole(User::LEARNER);
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

                                if (0 !== intval($wcOrderMeta->firstWhere('meta_key', '_order_total')->meta_value)) {
                                    // Give access to specific courses.
                                    $user->assignRole(User::CLIENT);
                                }
                            }
                        }
                    }

                    // Import profile picture, if any.
                    $avatarsDir = config('badgefactor2.wordpress.avatars_dir');
                    $avatarImage = null;

                    if (is_dir($avatarsDir.'/'.$wpUser->ID)) {
                        $matches = glob($avatarsDir.'/'.$wpUser->ID.'/*bpfull.*');
                        $avatarImage = isset($matches[0]) ? $matches[0] : null;
                    }

                    if (null !== $avatarImage && ! $user->getFirstMedia()) {

                        try {
                            $image = Image::make($avatarImage);
                            $user->addMediaFromBase64($image->encode('data-url'))
                                ->withCustomProperties([
                                    'alt' => $user->first_name . ' ' . $user->last_name,
                                ])
                                ->toMediaCollection('photo');
                        } catch (NotReadableException $e) {}
                    }

                });
            }
        );

        $this->info("\nAll done!");
    }
}
