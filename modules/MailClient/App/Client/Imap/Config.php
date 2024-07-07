<?php
 

namespace Modules\MailClient\App\Client\Imap;

class Config
{
    public function __construct(
        protected string $host,
        protected int $port,
        protected ?string $encryption,
        protected string $email,
        protected bool $validateCertificate,
        protected ?string $username,
        protected string $password,
    ) {
    }

    /**
     * Get the connection server/host
     */
    public function host(): string
    {
        return $this->host;
    }

    /**
     * Get the connection port
     */
    public function port(): int
    {
        return $this->port;
    }

    /**
     * Get the connection encryption type
     *
     * @return string|null ssl|tls|starttls
     */
    public function encryption(): ?string
    {
        return $this->encryption ?? null;
    }

    /**
     * Get the connection email address
     */
    public function email(): string
    {
        return $this->email;
    }

    /**
     * Whether to validate the certificate
     */
    public function validateCertificate(): bool
    {
        return $this->validateCertificate;
    }

    /**
     * Get connection username in case using different username
     * then the email address
     */
    public function username(): ?string
    {
        return $this->username;
    }

    /**
     * Get the connection password
     */
    public function password(): string
    {
        return $this->password;
    }
}
