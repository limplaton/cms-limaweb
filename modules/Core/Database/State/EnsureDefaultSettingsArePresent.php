<?php
 

namespace Modules\Core\Database\State;

use Modules\Core\App\Settings\DefaultSettings;

class EnsureDefaultSettingsArePresent
{
    public function __invoke(): void
    {
        if ($this->present()) {
            return;
        }

        settings()->flush();

        $defaultSettings = array_merge(DefaultSettings::get(), ['_seeded' => true]);

        foreach ($defaultSettings as $setting => $value) {
            settings()->set([$setting => $value]);
        }

        settings()->save();
    }

    private function present(): bool
    {
        return settings('_seeded') === true;
    }
}
