<?php

namespace Ctrlweb\BadgeFactor2\Listeners;

use Ctrlweb\BadgeFactor2\Models\User;
use Hautelook\Phpass\PasswordHash;
use Illuminate\Auth\Events\Attempting;
use Illuminate\Support\Facades\Hash;

class WordPressPasswordUpdate
{
    private $_user;

    public function handle(Attempting $event)
    {
        $this->_user = User::where('email', $event->credentials['email'])->first();
        if (!$this->_user) {
            return;
        }
        $this->checkAndUpdate($event->credentials['password']);
    }

    public function checkAndUpdate($value)
    {
        $passwordHash = new PasswordHash(8, true);
        // If the hash is md5 or phpass, verify and update if necessary.
        if ((32 >= strlen($this->_user->wp_password) && md5($value) === $this->_user->wp_password)
            || ($passwordHash->CheckPassword($value, $this->_user->wp_password))
        ) {
            $this->_user->password = Hash::make($value);
            $this->_user->save();
        }
    }
}
