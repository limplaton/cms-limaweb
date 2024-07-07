<?php
 

namespace Modules\Core\App\Http\Controllers\Api;

use App\Installer\RequirementsChecker;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Artisan;
use Modules\Core\App\Http\Controllers\ApiController;
use Modules\Core\App\Updater\Exceptions\UpdaterException;
use Modules\Core\App\Updater\Updater;
use Throwable;

class UpdateController extends ApiController
{
    /**
     * Get information about update.
     */
    public function index(Updater $updater): JsonResponse
    {
        return $this->response([
            'installed_version' => $updater->getVersionInstalled(),
            'latest_available_version' => $updater->getVersionAvailable(),
            'is_new_version_available' => $updater->isNewVersionAvailable(),
            'purchase_key' => $updater->getPurchaseKey(),
        ]);
    }

    /**
     * Perform an application update.
     */
    public function update(?string $purchaseKey = null): JsonResponse
    {
        // Update flag

        $requirements = app(RequirementsChecker::class);

        abort_if($requirements->fails('zip'), 409, __('core::update.update_zip_is_required'));

        // Save the purchase key for future usage
        if ($purchaseKey) {
            settings(['purchase_key' => $purchaseKey]);
        }

        /** @var \Modules\Core\App\Updater\Updater */
        $updater = app(Updater::class);

        $updater->usePurchaseKey($purchaseKey ?: '');

        if (! $updater->isNewVersionAvailable()) {
            throw new UpdaterException('No new version available', 409);
        }

        if (! app()->runningUnitTests()) {
            $updater->increasePhpIniValues();
        }

        Artisan::call('down', ['--render' => 'core::errors.updating']);

        try {
            $updater->update($updater->getVersionAvailable());
        } catch (Throwable $e) {
            Artisan::call('up');

            throw $e;
        } finally {
            Artisan::call('up');
        }

        return $this->response('', JsonResponse::HTTP_NO_CONTENT);
    }
}
