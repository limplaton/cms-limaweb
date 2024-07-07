<?php
 

namespace Modules\Core\App\Macros\Str;

class ClickableUrls
{
    /**
     * Check for links/emails/ftp in string to wrap in href
     *
     * @param  string  $string
     * @return string formatted string with href in any found
     */
    public function __invoke($string)
    {
        $string = ' '.$string;
        // in testing, using arrays here was found to be faster
        $string = preg_replace_callback('#([\s>])([\w]+?://[\w\\x80-\\xff\#$%&~/.\-;:=,?@\[\]+]*)#is', 'self::clickableUrlCallback', $string);
        $string = preg_replace_callback('#([\s>])((www|ftp)\.[\w\\x80-\\xff\#$%&~/.\-;:=,?@\[\]+]*)#is', 'self::clickableFtpCallback', $string);
        $string = preg_replace_callback('#([\s>])([.0-9a-z_+-]+)@(([0-9a-z-]+\.)+[0-9a-z]{2,})#i', 'self::clickableEmailCallback', $string);
        // this one is not in an array because we need it to run last, for cleanup of accidental links within links
        $string = preg_replace('#(<a( [^>]+?>|>))<a [^>]+?>([^>]+?)</a></a>#i', '$1$3</a>', $string);
        $string = trim($string);

        return $string;
    }

    /**
     * Callback for clickable
     */
    protected static function clickableUrlCallback($matches)
    {
        $ret = '';
        $url = $matches[2];
        if (empty($url)) {
            return $matches[0];
        }
        // removed trailing [.,;:] from URL
        if (in_array(substr($url, -1), [
            '.',
            ',',
            ';',
            ':',
        ]) === true) {
            $ret = substr($url, -1);
            $url = substr($url, 0, strlen($url) - 1);
        }

        return $matches[1]."<a href=\"$url\" rel=\"nofollow\" target='_blank'>$url</a>".$ret;
    }

    /**
     * Callback for clickable
     */
    protected static function clickableFtpCallback($matches)
    {
        $ret = '';
        $dest = $matches[2];
        $dest = 'http://'.$dest;
        if (empty($dest)) {
            return $matches[0];
        }
        // removed trailing [,;:] from URL
        if (in_array(substr($dest, -1), [
            '.',
            ',',
            ';',
            ':',
        ]) === true) {
            $ret = substr($dest, -1);
            $dest = substr($dest, 0, strlen($dest) - 1);
        }

        return $matches[1]."<a href=\"$dest\" rel=\"nofollow\" target='_blank'>$dest</a>".$ret;
    }

    /**
     * Callback for clickable
     */
    protected static function clickableEmailCallback($matches)
    {
        $email = $matches[2].'@'.$matches[3];

        return $matches[1]."<a href=\"mailto:$email\">$email</a>";
    }
}
