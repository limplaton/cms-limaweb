<?php
 

namespace Modules\Calls\App\VoIP\Contracts;

use Illuminate\Http\Request;

interface Tokenable
{
    /**
     * Create new client token for the logged in user
     *
     *
     * @return string
     */
    public function newToken(Request $request);
}
