<?php
 

namespace Modules\Core\App\Macros\Str;

class IsBase64Encoded
{
    /**
     * Check whether a give string is already encoded in base64
     *
     * @param  string  $str
     * @return bool
     */
    public function __invoke($str)
    {
        $decoded = base64_decode($str, true);

        // Check if there is no invalid character in string
        if (! preg_match('/^[a-zA-Z0-9\/\r\n+]*={0,2}$/', $str)) {
            return false;
        }

        // Decode the string in strict mode and send the response
        if (! base64_decode($str, true)) {
            return false;
        }

        // Encode and compare it to original one
        return ! (base64_encode($decoded) != $str);
    }
}
