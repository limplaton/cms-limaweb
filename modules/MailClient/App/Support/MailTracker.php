<?php
 

namespace Modules\MailClient\App\Support;

use Illuminate\Support\Str;
use Modules\MailClient\App\Client\Compose\AbstractComposer;
use Modules\MailClient\App\Models\EmailAccountMessage;

class MailTracker
{
    public function addTrackers($html, $hash)
    {
        $html = $this->injectTrackingPixel($html, $hash);

        return $this->injectLinkTracker($html, $hash);
    }

    protected function injectTrackingPixel($html, $hash)
    {
        $pixel = '<img src="'.route('mail-tracker.open', $hash).'" alt="" width="1" height="1" border="0" style="height:1px!important;width:1px!important;border-width:0!important;margin-top:0!important;margin-bottom:0!important;margin-right:0!important;margin-left:0!important;padding-top:0!important;padding-bottom:0!important;padding-right:0!important;padding-left:0!important" />';

        $linebreak = Str::random(32);
        $html = str_replace("\n", $linebreak, $html);

        if (preg_match('/^(.*<body[^>]*>)(.*)$/', $html, $matches)) {
            $html = $matches[1].$matches[2].$pixel;
        } else {
            $html = $html.$pixel;
        }

        $html = str_replace($linebreak, "\n", $html);

        return $html;
    }

    protected function injectLinkTracker($html, $hash)
    {
        $html = preg_replace_callback(
            '/(<a[^>]*href=["])([^"]*)/',
            function ($matches) use ($hash) {
                if (empty($matches[2])) {
                    $url = app()->make('url')->to('/');
                } else {
                    $url = str_replace('&amp;', '&', $matches[2]);
                }

                return $matches[1].route(
                    'mail-tracker.link',
                    [
                        'l' => $url,
                        'h' => $hash,
                    ]
                );
            },
            $html
        );

        return $html;
    }

    public function createTrackers(AbstractComposer $message)
    {
        do {
            $hash = Str::random(32);
            $used = EmailAccountMessage::where('hash', $hash)->count();
        } while ($used > 0);

        $message->addHeader('X--Hash', $hash);

        $html = $message->getClient()->getSmtp()->getHtmlBody();

        $message->htmlBody(
            $this->addTrackers($html, $hash)
        );
    }
}
