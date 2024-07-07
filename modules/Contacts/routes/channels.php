<?php
 

use Illuminate\Support\Facades\Broadcast;
use Modules\Contacts\App\Models\Company;
use Modules\Contacts\App\Models\Contact;

Broadcast::channel('Modules.Contacts.App.Models.Contact.{contactId}', function ($user, $contactId) {
    return $user->can('view', Contact::findOrFail($contactId));
});

Broadcast::channel('Modules.Contacts.App.Models.Company.{companyId}', function ($user, $companyId) {
    return $user->can('view', Company::findOrFail($companyId));
});
