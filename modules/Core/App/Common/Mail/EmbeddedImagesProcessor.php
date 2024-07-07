<?php
 

namespace Modules\Core\App\Common\Mail;

use Symfony\Component\Mime\MimeTypes;

class EmbeddedImagesProcessor
{
    /**
     * Process embedded images and execute action on each embedded image
     *
     * @param  string  $body
     * @param  \Closure  $callback
     * @return string|null
     */
    public function __invoke($body, $callback)
    {
        if (is_null($body)) {
            return $body;
        }

        $body = preg_replace_callback(
            '/<img(.*)src(\s*)=(\s*)["\'](.*)["\']/U',
            function ($matches) use ($callback) {
                if (count($matches) === 5) {
                    // 1st match contains any data between '<img' and 'src' parts (e.g. 'width=100')
                    $imgConfig = $matches[1];

                    // 4th match contains src attribute value
                    $srcData = $matches[4];

                    if (str_starts_with($srcData, 'data:image')) {
                        [$mime, $content] = explode(';', $srcData);
                        [$encoding, $file] = explode(',', $content);

                        $mime = str_replace('data:', '', $mime);
                        $fileName = sprintf('%s.%s', uniqid(), MimeTypes::getDefault()->getExtensions($mime)[0] ?? null);

                        $id = $callback(ContentDecoder::decode($file, $encoding), $fileName, $mime);

                        return sprintf('<img%ssrc="%s"', $imgConfig, $id);
                    }
                }

                return $matches[0];
            },
            $body
        );

        return $body;
    }
}
