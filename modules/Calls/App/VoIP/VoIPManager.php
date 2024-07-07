<?php
 

namespace Modules\Calls\App\VoIP;

use Illuminate\Support\Manager;
use Modules\Calls\App\VoIP\Clients\Twilio;
use Modules\Calls\App\VoIP\Contracts\ReceivesEvents;

class VoIPManager extends Manager
{
    /**
     * Get the default driver name.
     *
     * @return string
     */
    public function getDefaultDriver()
    {
        return $this->container['config']['voip.client'];
    }

    /**
     * Create Twilio VoIP driver
     *
     * @return \Modules\Calls\App\VoIP\Clients\Twilio
     */
    public function createTwilioDriver()
    {
        return new Twilio($this->container['config']['twilio']);
    }

    /**
     * Check whether the driver receives events
     *
     * @param  string|null  $driver
     * @return bool
     */
    public function shouldReceivesEvents($driver = null)
    {
        return $this->driver($driver) instanceof ReceivesEvents;
    }
}
