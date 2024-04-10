<?php

namespace Ctrlweb\BadgeFactor2\Listeners;

use Ctrlweb\BadgeFactor2\Events\EmailChangeValidated;
use Ctrlweb\BadgeFactor2\Models\User;
use Ctrlweb\BadgeFactor2\Services\Badgr\BackpackAssertion;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;


class UpdateEmailInExternalSystems implements ShouldQueue
{
    public function handle(EmailChangeValidated $event)
    {
        $this->inBadgr($event->userId, $event->oldEmail, $event->newEmail);
        $this->inLMS($event->userId, $event->oldEmail, $event->newEmail);
    }

    private function inBadgr(int $userId, string $oldEmail, string $newEmail): void
    {
        $badgrDb = DB::connection('badgr');

        $badgrDb->beginTransaction();
        $error = false;

        $badgrUser = $badgrDb->select('select * from users where email = ?', [$oldEmail]);

        if (empty($badgrUser)) {
            Log::error('Badge User email update failed: email does not exist!');
            $error = true;
        } else {
            $badgrUser = array_pop($badgrUser);

            // Update Badgr user.
            $rowsAffected = $badgrDb->update(
                'update users set email = ? where id = ?',
                [
                    $newEmail,
                    $badgrUser->id,
                ]
            );
            if ($rowsAffected === 0) {
                Log::error('Badgr User email update failed: Badgr User Entity ID not found!');
                $error = true;
            } elseif ($rowsAffected > 1) {
                Log::error('An error occured while updating Badgr User Email: there seems to be user duplicates in Badgr!');
                $error = true;
            }

            // Update Badgr account email address.
            $rowsAffected = $badgrDb->update(
                'update account_emailaddress set email = ?, verified = true where user_id = ?',
                [
                    $newEmail,
                    $badgrUser->id,
                ]
            );
            if ($rowsAffected === 0) {
                Log::error('Badgr User email update failed: Badgr User Entity ID not found!');
                $error = true;
            } elseif ($rowsAffected > 1) {
                Log::error('An error occured while updating Badgr User Email: there seems to be user duplicates in Badgr!');
                $error = true;
            }

            // Reassign Badgr badge instances.
            $rowsAffected = $badgrDb->update(
                'update issuer_badgeinstance set recipient_identifier = ? where recipient_identifier = ?',
                [
                    $newEmail,
                    $oldEmail,
                ]
            );
            // Rebake all Badgr badge instances in backpack.
            $user = User::find($userId);
            (new BackpackAssertion($user))->rebake();
        }

        if ($error) {
            $badgrDb->rollBack();
        } else {
            $badgrDb->commit();
        }
    }

    public function inLMS(int $userId, string $oldEmail, string $newEmail): void
    {
        $encryption_method = config('badgefactor2.encryption.algorithm');
        $encryption_key = config('badgefactor2.encryption.secret_key');
        $encryption_iv = substr(hash('sha256', config('badgefactor2.encryption.secret_iv')), 0, 16);

        $payload = json_encode([
            'old_email' => $oldEmail,
            'new_email' => $newEmail,
        ]);
        Http::asForm()
            ->acceptJson()
            ->withOptions([
                'verify' => false,
            ])
            ->post(
                config('badgefactor2.wordpress.base_url').'/wp-admin/admin-ajax.php?action=update_email_from_bf2',
                [
                    'payload' => openssl_encrypt($payload, $encryption_method, $encryption_key, 0, $encryption_iv),
                ]
            );
    }
}
