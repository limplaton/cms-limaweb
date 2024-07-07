<?php
 

namespace Modules\MailClient\App\Client\Outlook;

use Illuminate\Support\Collection;

class DeltaMessageCollection extends Collection
{
    protected static ?string $deltaLink;

    public function setDeltaLink(?string $link): static
    {
        static::$deltaLink = $link;

        return $this;
    }

    public static function getDeltaLink(): ?string
    {
        return static::$deltaLink;
    }
}
