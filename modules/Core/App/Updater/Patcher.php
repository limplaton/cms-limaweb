<?php
 

namespace Modules\Core\App\Updater;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Modules\Core\App\Updater\Events\PatchApplied;
use Modules\Core\App\Updater\Exceptions\CannotOpenZipArchiveException;
use Modules\Core\App\Updater\Exceptions\HasWrongPermissionsException;
use Modules\Core\App\Updater\Exceptions\InvalidPurchaseKeyException;
use Modules\Core\App\Updater\Exceptions\PurchaseKeyEmptyException;
use Modules\Core\App\Updater\Exceptions\UpdaterException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class Patcher
{
    use ChecksPermissions, ExchangesPurchaseKey;

    /**
     * @var int
     */
    const INVALID_PURCHASE_KEY_CODE = 498;

    /**
     * @var int
     */
    const PURCHASE_KEY_EMPTY_CODE = 497;

    /**
     * Available patches.
     */
    protected array $patches = [];

    /**
     * The path where the update files will be extracted.
     */
    protected string $basePath;

    /**
     * Initialize new Patcher instance.
     */
    public function __construct(protected Client $client, protected Filesystem $filesystem, protected array $config)
    {
        $this->basePath = base_path();
        $filesystem->ensureDirectoryExists($config['download_path']);
    }

    /**
     * Download the given patch by token.
     */
    public function download(string $token): BinaryFileResponse
    {
        $patch = $this->find($token);

        $this->makeDownloadRequest($patch);

        return response()->download(
            $patch->getStoragePath(),
            'v'.$this->config['version_installed'].'-'.basename($patch->getStoragePath())
        )->deleteFileAfterSend(true);
    }

    /**
     * Retrieve the given patch by token.
     *
     * @throws \Modules\Core\App\Updater\Exceptions\UpdaterException
     */
    public function fetch(Patch|string $patch): Patch
    {
        if (is_string($patch)) {
            $patch = $this->find($patch);
        }

        if (! $patch->archive()->exists()) {
            $this->makeDownloadRequest($patch);
        }

        return $patch;
    }

    /**
     * Find patch by the given token.
     */
    public function find(string $token): Patch
    {
        $patches = $this->getAvailablePatches();

        return tap($patches->first(function ($patch) use ($token) {
            return $patch->token() === $token;
        }), function ($patch) use ($token) {
            throw_if(is_null($patch), new UpdaterException("The patch {$token} could not be found.", 404));
        });
    }

    /**
     * Apply the given patch.
     *
     * @throws \Modules\Core\App\Updater\Exceptions\HasWrongPermissionsException
     * @throws \Modules\Core\App\Updater\Exceptions\UpdaterException
     */
    public function apply(Patch|string $patch, bool $deleteSource = true): bool
    {
        if (! $this->checkPermissions($this->basePath, $this->config['permissions']['exclude_folders'])) {
            throw new HasWrongPermissionsException;
        }

        if (is_string($patch)) {
            $patch = $this->fetch($patch);
        }

        throw_if(
            $patch->version() != $this->config['version_installed'],
            new UpdaterException('This patch does not belongs to the current version.', 409)
        );

        if (! $patch->archive()->exists()) {
            $this->makeDownloadRequest($patch);
        }

        $cleaner = new StaleBuildAssetsCleaner($this->filesystem);

        try {
            $cleaner->capture();

            $patch->archive()
                ->after($cleaner->clean(...))
                ->extract($this->basePath, $deleteSource);
        } catch (CannotOpenZipArchiveException $e) {
            // Delete the source in case of invalid .zip archive
            $patch->archive()->deleteSource();

            throw $e;
        }

        $patch->markAsApplied();

        PatchApplied::dispatch($patch);

        return true;
    }

    /**
     * Get the available patches for the version.
     *
     * @throws \Modules\Core\App\Updater\Exceptions\UpdaterException
     */
    public function getAvailablePatches(): Collection
    {
        $version = $this->config['version_installed'];

        if (array_key_exists($version, $this->patches)) {
            return $this->patches[$version];
        }

        if (empty($this->config['patches_url'])) {
            throw new UpdaterException('Patches URL not specified, please enter a valid URL in your config.', 500);
        }

        try {
            $response = $this->client->get($this->config['patches_url'].'/'.$version, [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
            ]);
        } catch (RequestException $e) {
            throw new UpdaterException($e->getMessage(), $e->getCode());
        }

        $patches = json_decode($response->getBody()->getContents());

        return $this->patches[$version] = collect($patches)->map(function (object $patch) {
            $patch->date = Carbon::parse($patch->date);

            return $patch;
        })->map(function (object $patch) use ($version) {
            $downloadUrl = Updater::createInternalRequestUrl(
                $this->config['patches_url'].'/'.$version.'/'.$patch->token
            );

            return (new Patch($patch))
                ->setDownloadUrl($downloadUrl)
                ->setAccessToken($this->getPurchaseKey())
                ->setStoragePath(
                    Str::finish($this->config['download_path'], DIRECTORY_SEPARATOR).$patch->token.'.zip'
                );
        })->sortBy([
            [fn (Patch $patch) => $patch->isApplied(), 'asc'],
            ['date', 'asc'],
        ])->values();
    }

    /**
     * Download the patch.
     */
    protected function makeDownloadRequest(Patch $patch): void
    {
        try {
            $patch->download($this->client);
        } catch (ClientException $e) {
            if ($e->getCode() === static::INVALID_PURCHASE_KEY_CODE) {
                throw new InvalidPurchaseKeyException;
            } elseif ($e->getCode() === static::PURCHASE_KEY_EMPTY_CODE) {
                throw new PurchaseKeyEmptyException;
            }

            throw $e;
        }
    }
}
