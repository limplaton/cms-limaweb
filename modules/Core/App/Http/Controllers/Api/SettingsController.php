<?php
 

namespace Modules\Core\App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Modules\Core\App\Http\Controllers\ApiController;
use Modules\Core\App\Http\Requests\SettingRequest;

class SettingsController extends ApiController
{
    /**
     * Get the application settings.
     */
    public function index(): JsonResponse
    {
        return $this->response(
            collect(settings()->all())->reject(fn ($value, $name) => Str::startsWith($name, '_'))
        );
    }

    /**
     * Persist the settings in storage.
     */
    public function save(SettingRequest $request): JsonResponse
    {
        $request->saveSettings();

        return $this->response(settings()->all());
    }
}
