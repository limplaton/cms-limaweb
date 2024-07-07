<?php
 

namespace Modules\Users\App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Users\App\Models\User;

class UserAvatarService
{
    /**
     * Store the given user avatar.
     */
    public function store(User $user, UploadedFile $file): User
    {
        static::remove($user);

        $user->fill(['avatar' => $file->store('avatars', 'public')])->save();

        return $user;
    }

    /**
     * Delete user avatar
     */
    public static function remove(User $user): void
    {
        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }
    }
}
