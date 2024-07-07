<?php

use Modules\Brands\App\Models\Brand;
use Modules\Core\App\Updater\UpdatePatcher;

return new class extends UpdatePatcher
{
    public function run(): void
    {
        if ($this->missingDefaultBrand()) {
            $this->createDefaultBrand();
        }

        settings(['_php_version' => PHP_VERSION]);
    }

    public function shouldRun(): bool
    {
        return $this->missingDefaultBrand() ||
            empty(settings('_php_version'));
    }

    protected function createDefaultBrand(): void
    {
        Brand::create([
            'name' => config('app.name'),
            'display_name' => config('app.name'),
            'is_default' => true,
            'config' => [
                'primary_color' => '#4f46e5',
            ],
        ]);
    }

    protected function missingDefaultBrand(): bool
    {
        return Brand::count() === 0;
    }
};
