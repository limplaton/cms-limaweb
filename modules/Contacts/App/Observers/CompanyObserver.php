<?php
 

namespace Modules\Contacts\App\Observers;

use Modules\Contacts\App\Models\Company;

class CompanyObserver
{
    /**
     * Handle the Contact "deleting" event.
     */
    public function deleting(Company $company): void
    {
        if ($company->isForceDeleting()) {
            $company->purge();
        }
    }
}
