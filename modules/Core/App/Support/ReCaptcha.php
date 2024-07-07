<?php
 

namespace Modules\Core\App\Support;

use Illuminate\Http\Request;

class ReCaptcha
{
    /**
     * The ReCaptcha site key
     */
    protected ?string $siteKey = null;

    /**
     * The ReCaptcha secret key
     */
    protected ?string $secretKey = null;

    /**
     * IP addreses that validation should be skipped
     */
    protected array $skippedIps = [];

    /**
     * Initialize new ReCaptcha instance.
     */
    public function __construct(protected Request $request)
    {
    }

    /**
     * Get the reCaptcha site key
     */
    public function getSiteKey(): ?string
    {
        return $this->siteKey;
    }

    /**
     * Set the reCaptcha site key
     */
    public function setSiteKey(?string $key): static
    {
        $this->siteKey = $key;

        return $this;
    }

    /**
     * Get the recaptcha secret key
     */
    public function getSecretKey(): ?string
    {
        return $this->secretKey;
    }

    /**
     * Set the reCaptcha secret key
     */
    public function setSecretKey(?string $key): static
    {
        $this->secretKey = $key;

        return $this;
    }

    /**
     * Get an array of IP addresses that a reCaptcha should be skipped
     */
    public function getSkippedIps(): array
    {
        return array_filter(array_map('trim', $this->skippedIps));
    }

    /**
     * Set IP addresses that a reCaptcha should be skipped
     */
    public function setSkippedIps(array|string $ips)
    {
        if (is_string($ips)) {
            $ips = explode(',', $ips);
        }

        $this->skippedIps = $ips;

        return $this;
    }

    /**
     * Determine whether the reCaptcha validation should be skipped
     */
    public function shouldSkip(?string $ip = null): bool
    {
        return in_array($ip ?? $this->request->getClientIp(), $this->getSkippedIps());
    }

    /**
     * Check whether the reCaptcha is configured
     */
    public function configured(): bool
    {
        return ! empty($this->getSiteKey()) && ! empty($this->getSecretKey());
    }

    /**
     * Determine whether the reCaptcha validation should be shown
     */
    public function shouldShow(?string $ip = null): bool
    {
        if (! $this->configured()) {
            return false;
        }

        return ! $this->shouldSkip($ip);
    }
}
