<?php
 

namespace Modules\Core\App\Updater;

use GuzzleHttp\ClientInterface;
use Modules\Core\App\Updater\Exceptions\UpdaterException;
use Psr\Http\Message\ResponseInterface;

trait DownloadsFiles
{
    protected ?string $accessTokenPrefix = 'Bearer ';

    protected ?string $accessToken = null;

    /**
     * Url to download the release from.
     */
    protected ?string $downloadUrl = null;

    /**
     * Path to download the release to.
     * Example: /tmp/release-1.1.zip.
     */
    protected ?string $storagePath = null;

    public function getDownloadUrl(): ?string
    {
        return $this->downloadUrl;
    }

    public function setDownloadUrl(string $downloadUrl): static
    {
        $this->downloadUrl = $downloadUrl;

        return $this;
    }

    public function getStoragePath(): ?string
    {
        return $this->storagePath;
    }

    /**
     * @param  bool  $createDirectory
     */
    public function setStoragePath(string $storagePath): static
    {
        $this->storagePath = $storagePath;

        return $this;
    }

    /**
     * Download the update file.
     */
    public function download(ClientInterface $client): ResponseInterface
    {
        throw_if(empty($this->getStoragePath()), new UpdaterException('No storage path set.', 500));
        throw_if(empty($this->getDownloadUrl()), new UpdaterException('Download URL not provided.', 500));

        return $client->request(
            'GET',
            $this->getDownloadUrl(),
            [
                'sink' => $this->getStoragePath(),
                'headers' => $this->hasAccessToken() ? ['Authorization' => $this->getAccessToken()] : [],
            ]
        );
    }

    /**
     * Get the access token.
     */
    public function getAccessToken(bool $withPrefix = true): ?string
    {
        if ($withPrefix && $this->accessTokenPrefix) {
            return $this->accessTokenPrefix.$this->accessToken;
        }

        return $this->accessToken;
    }

    /**
     * Set the access token.
     */
    public function setAccessToken(string $token): static
    {
        $this->accessToken = $token;

        return $this;
    }

    /**
     * Check whether there is access token configured.
     */
    public function hasAccessToken(): bool
    {
        return ! empty($this->accessToken);
    }

    /**
     * Set custom access token prefix.
     */
    public function setAccessTokenPrefix(?string $prefix): static
    {
        $this->accessTokenPrefix = $prefix;

        return $this;
    }

    /**
     * Get the access token prefix.
     */
    public function getAccessTokenPrefix(): ?string
    {
        return $this->accessTokenPrefix;
    }
}
