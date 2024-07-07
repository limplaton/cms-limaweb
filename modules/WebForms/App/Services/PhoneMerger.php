<?php
 

namespace Modules\WebForms\App\Services;

use Illuminate\Database\Eloquent\Collection;
use Modules\Contacts\App\Models\Phone;

class PhoneMerger
{
    public function merge(Collection|array $oldPhones, array $newPhones): array
    {
        // Create an array where keys are phone numbers for easy lookup
        $mergedPhones = [];

        if ($oldPhones instanceof Collection) {
            $oldPhones = $oldPhones->map(
                fn (Phone $phone) => ['number' => $phone->number, 'type' => $phone->type]
            );
        }

        foreach ($oldPhones as $entry) {
            $mergedPhones[$entry['number']] = $entry;
        }

        foreach ($newPhones as $entry) {
            // If number exists in $mergedPhones, it will replace the old entry
            $mergedPhones[$entry['number']] = $entry; // Replace or add new entry
        }

        return array_values($mergedPhones);
    }
}
