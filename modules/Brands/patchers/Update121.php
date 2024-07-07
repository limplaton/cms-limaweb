<?php
 

use Illuminate\Support\Arr;
use Modules\Brands\App\Models\Brand;
use Modules\Core\App\Facades\Innoclapps;
use Modules\Core\App\Updater\UpdatePatcher;

return new class extends UpdatePatcher
{
    public function run(): void
    {
        if (is_null(settings('_brands_localization_migrated'))) {
            $localizeableConfigKeys = [
                'document.mail_subject',
                'document.mail_message',
                'document.mail_button_text',
                'document.signed_mail_subject',
                'document.signed_mail_message',
                'document.signed_thankyou_message',
                'document.accepted_thankyou_message',
                'signature.bound_text',
            ];

            Brand::get()->each(function ($brand) use ($localizeableConfigKeys) {
                foreach ($localizeableConfigKeys as $key) {
                    $config = $brand->config;
                    $value = Arr::get($config, $key);

                    if (! is_array($value)) {
                        $newValue = [];

                        foreach (Innoclapps::locales() as $locale) {
                            $newValue[$locale] = $value;
                        }

                        Arr::set($config, $key, $newValue);
                        $brand->config = $config;
                    }
                }
                $brand->saveQuietly();
            });

            settings(['_brands_localization_migrated' => true]);
        }
    }

    public function shouldRun(): bool
    {
        return is_null(settings('_brands_localization_migrated'));
    }
};
