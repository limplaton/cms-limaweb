<?php
 

namespace Modules\Core\App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Modules\Core\App\Http\Controllers\ApiController;

class LogoController extends ApiController
{
    /**
     * Save company logo
     */
    public function store(string $type, Request $request): JsonResponse
    {
        // Logo store flag

        $logoName = 'logo_'.$type;

        $request->validate([
            $logoName => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:1024',
        ]);

        $this->destroy($type);

        if ($filename = $request->file($logoName)->store('/', 'public')) {
            $logoUrl = "/storage/$filename";
            settings([$logoName => $logoUrl]);

            return $this->response(['logo' => $logoUrl]);
        }

        abort(500, 'Failed to save the logo.');
    }

    /**
     * Remove company logo
     */
    public function destroy(string $type): JsonResponse
    {
        $optionName = 'logo_'.$type;
        $currentLogo = settings($optionName);
        $filename = basename($currentLogo);

        if (! empty($currentLogo) && $this->disk()->exists($filename)) {
            $this->disk()->delete($filename);
        }

        settings()->forget($optionName)->save();

        return $this->response('', JsonResponse::HTTP_NO_CONTENT);
    }

    /**
     * Get the logo storage disk
     *
     * @return mixed
     */
    protected function disk()
    {
        return Storage::disk('public');
    }
}
