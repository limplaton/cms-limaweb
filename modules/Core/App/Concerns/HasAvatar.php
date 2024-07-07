<?php
 

namespace Modules\Core\App\Concerns;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Storage;

/** @mixin \Modules\Core\App\Models\Model */
trait HasAvatar
{
    /**
     * Get Gravatar URL.
     */
    public function getGravatarUrl(?string $email = null, string|int $size = '40'): string
    {
        $email ??= $this->email ?? '';

        return 'https://www.gravatar.com/avatar/'.md5(strtolower($email)).'?s='.$size;
    }

    /**
     * Get the model avatar URL.
     */
    public function avatarUrl(): Attribute
    {
        return Attribute::get(function () {
            if (is_null($this->avatar)) {
                return $this->getGravatarUrl();
            }

            return $this->uploadedAvatarUrl;
        });
    }

    /**
     * Get the actual uploaded path URL for src image.
     */
    public function uploadedAvatarUrl(): Attribute
    {
        return Attribute::get(function () {
            if (is_null($this->avatar)) {
                return null;
            }

            return Storage::url($this->avatar);
        });
    }
}
