<?php
 

namespace Modules\Calls\App\VoIP\Contracts;

use Illuminate\Http\Request;

interface ReceivesEvents
{
    /**
     * Set the call events URL
     *
     * @param  string  $url  The URL the client events webhook should be pointed to
     */
    public function setEventsUrl(string $url): static;

    /**
     * Handle the VoIP service events request
     *
     *
     * @return mixed
     */
    public function events(Request $request);
}
