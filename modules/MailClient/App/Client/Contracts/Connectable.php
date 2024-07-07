<?php
 

namespace Modules\MailClient\App\Client\Contracts;

use Modules\MailClient\App\Client\Imap\Config;

interface Connectable
{
    /**
     * Connect to server
     *
     * @return mixed
     */
    public function connect();

    /**
     * Test the connection
     *
     * @return mixed
     */
    public function testConnection();

    /**
     * Get the connection config
     */
    public function getConfig(): Config;
}
