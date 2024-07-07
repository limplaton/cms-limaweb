<?php
 

namespace Modules\Core\Database\State;

use Modules\Core\App\Facades\MailableTemplates;

class EnsureMailableTemplatesAreSeeded
{
    public function __invoke(): void
    {
        MailableTemplates::seed();
    }
}
