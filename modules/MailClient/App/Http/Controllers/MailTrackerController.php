<?php
 

namespace Modules\MailClient\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\MailClient\App\Models\EmailAccountMessage;
use Modules\MailClient\App\Models\MessageLinksClick;

class MailTrackerController extends Controller
{
    public function opens($hash)
    {
        $response = response($this->createPixel(), 200)
            ->header('Content-type', 'image/gif')
            ->header('Content-Length', 42)
            ->header('Cache-Control', 'private, no-cache, no-cache=Set-Cookie, proxy-revalidate')
            ->header('Expires', 'Wed, 11 Jan 2000 12:59:00 GMT')
            ->header('Last-Modified', 'Wed, 11 Jan 2006 12:59:00 GMT')
            ->header('Pragma', 'no-cache');

        if (! $this->isWithinApplication() && Auth::guest()) {
            if ($message = EmailAccountMessage::where('hash', $hash)->first()) {
                $message->opens++;

                if (! $message->opened_at) {
                    $message->opened_at = now();
                }

                $message->save();
            }
        }

        return $response;
    }

    public function link(Request $request)
    {
        $url = $request->l;
        $hash = $request->h;

        return $this->linkClicked($url, $hash);
    }

    protected function linkClicked($url, $hash)
    {
        if (! $url) {
            $url = '/';
        }

        /**
         * I found that recently we are getting a lot of false clicks/reads because automatic
         * spam protection and phishing protection are checking the urls in our emails as soon as they are sent.
         *
         * The SPAM protection appears to call "HEAD" calls instead of "GET" as they aren't interested in the result,
         * just that things seem to redirect somewhere good.
         */
        if (request()->isMethod('GET') && ! $this->isWithinApplication() && Auth::guest()) {
            $message = EmailAccountMessage::where('hash', $hash)->first();

            if ($message) {
                $this->recordLinkClick($message, $url);
            }
        }

        return redirect($url);
    }

    protected function recordLinkClick($message, $url)
    {
        $click = MessageLinksClick::where('url', $url)
            ->where('message_id', $message->id)
            ->first();

        if ($click) {
            $click->total++;
            $click->save();
        } else {
            $message->linksClicks()->create([
                'url' => $url,
            ]);
        }

        // If no opened at but has a clicked event then we can assume that it was in fact opened, the tracking pixel may have been blocked
        if (! $message->opened_at) {
            $message->opened_at = now();
        }

        if (! $message->clicked_at) {
            $message->clicked_at = now();
        }

        $message->clicks++;

        $message->save();
    }

    protected function isWithinApplication()
    {
        $referer = request()->headers->get('referer');

        return $referer && rtrim($referer, '/') == rtrim(config('app.url'), '/');
    }

    protected function createPixel()
    {
        // Create a 1x1 transparent pixel
        return sprintf('%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c', 71, 73, 70, 56, 57, 97, 1, 0, 1, 0, 128, 255, 0, 192, 192, 192, 0, 0, 0, 33, 249, 4, 1, 0, 0, 0, 0, 44, 0, 0, 0, 0, 1, 0, 1, 0, 0, 2, 2, 68, 1, 0, 59);
    }
}
