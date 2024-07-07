<?php
 

namespace Modules\MailClient\App\Support;

use EmailReplyParser\Parser\EmailParser;
use Illuminate\Support\Str;
use KubAT\PhpSimple\HtmlDomParser;
use Modules\Core\App\Support\AutoParagraph;
use Modules\MailClient\App\Models\EmailAccountMessage;
use Pelago\Emogrifier\CssInliner;
use Pelago\Emogrifier\HtmlProcessor\CssToAttributeConverter;
use Pelago\Emogrifier\HtmlProcessor\HtmlPruner;

class EmailAccountMessageBody
{
    /**
     * Preview text cache.
     *
     * @var null|string
     */
    protected $previewText = null;

    /**
     * Parsed cache.
     *
     * @var null|\EmailReplyParser\Email
     */
    protected $parsed = null;

    /**
     * Reply regex when email is sent via app.
     */
    const REPLY_REGEX = '/(<div class="(_attr|c__attr)">)(.*)(<\/div>)/mU';

    /**
     * Create new EmailAccountMessageBody instance.
     */
    public function __construct(protected EmailAccountMessage $message)
    {
    }

    /**
     * Versions 1.3.4 and below had an issue where when forwarding and replying to message the text was not inserted
     * into the original message <body></body> (if full HTML document) which was causing emogrifier to fail
     * with error message "There is no HEAD element. This should never happen".
     *
     * We will make sure that the content is moved into the body in case the message is affected by this.
     *
     * This function should be safe to be removed in the a couple of years.
     */
    protected function fixedHtmlBody()
    {
        $htmlContent = $this->message->html_body;

        if (! is_string($htmlContent)) {
            return $htmlContent;
        }

        $beforeHtmlRegex = '/^(.*?)(<!DOCTYPE|<html)/is';
        $afterHtmlRegex = '/<\/html>(.*)$/is';

        preg_match($beforeHtmlRegex, $htmlContent, $beforeMatches);
        preg_match($afterHtmlRegex, $htmlContent, $afterMatches);

        $htmlContent = preg_replace($beforeHtmlRegex, '\2', $htmlContent);
        $htmlContent = preg_replace($afterHtmlRegex, '', $htmlContent);

        if (! empty($beforeMatches[1])) {
            $htmlContent = preg_replace('/(<body[^>]*>)/i', '\1'.$beforeMatches[1], $htmlContent);
        }

        if (! empty($afterMatches[1])) {
            $htmlContent = preg_replace('/(<\/body>)/i', $afterMatches[1].'\1', $htmlContent);
        }

        return $htmlContent;
    }

    /**
     * Get the message preview text.
     *
     * @return string
     */
    public function previewText()
    {
        if ($this->previewText) {
            return $this->previewText;
        }

        $htmlBody = $this->fixedHtmlBody();
        $textBody = $this->message->text_body;

        if (is_null($htmlBody) || empty(trim($htmlBody))) {
            return $this->previewText = AutoParagraph::wrap($textBody);
        }

        $cssInliner = CssInliner::fromHtml($this->applyBodyFormats($htmlBody))->inlineCss();

        $domDocument = $cssInliner->getDomDocument();
        HtmlPruner::fromDomDocument($domDocument)
            ->removeElementsWithDisplayNone();

        $finalHtml = CssToAttributeConverter::fromDomDocument($domDocument)
            ->convertCssToVisualAttributes()
            ->renderBodyContent();

        return $this->previewText = Str::clickable(
            $this->prefixStaleClasses($finalHtml)
        );
    }

    /**
     * Get the message visible text.
     *
     * @return string
     */
    public function visibleText()
    {
        if (
            $this->message->is_sent_via_app &&
            $this->message->isReply() &&
            preg_match(static::REPLY_REGEX, $this->previewText(), $matches)
        ) {
            return Str::before($this->previewText(), $matches[0]);
        }

        $visibleText = $this->parseMessageForPreview()->getVisibleText();

        if (empty($visibleText)) {
            return $this->previewText();
        }

        return $this->applyBodyFormats($visibleText);
    }

    /**
     * Get the message the text that should be hidden.
     *
     * @return string
     */
    public function hiddenText()
    {
        if (
            $this->message->is_sent_via_app &&
            $this->message->isReply() &&
            preg_match(static::REPLY_REGEX, $this->previewText(), $matches)
        ) {
            return $matches[0].Str::after($this->previewText(), $matches[0]);
        }

        $fragments = $this->parseMessageForPreview()->getFragments();

        $hiddenFragments = array_filter($fragments, function ($fragment) {
            return $fragment->isHidden();
        });

        return $this->applyBodyFormats(rtrim(implode("\n", $hiddenFragments)));
    }

    /**
     * Check whether the given message body has HTML.
     *
     * @param  string  $text
     * @return string
     */
    protected function applyBodyFormats($text)
    {
        if (! preg_match('/<[^<]+>/', $text, $m) != 0) {
            return AutoParagraph::wrap($text);
        }

        // For HTML, open all external links in new tab
        return preg_replace(
            '/(<a href="https?:[^"]+")>/is',
            '\\1 target="_blank">',
            $text
        );
    }

    /**
     * Parse the message with the EmailReplyParser.
     *
     * @return \EmailReplyParser\Email
     */
    protected function parseMessageForPreview()
    {
        if ($this->parsed) {
            return $this->parsed;
        }

        if (! $this->message->html_body) {
            return (new EmailParser)->parse($this->message->text_body ?? '');
        }

        // Encode any entities to UTF-8 as the CssInliner expects UTF-8 encoded string
        $cssInliner = CssInliner::fromHtml(
            mb_convert_encoding($this->fixedHtmlBody(), 'HTML-ENTITIES', 'UTF-8')
        )->inlineCss();

        $domDocument = $cssInliner->getDomDocument();

        HtmlPruner::fromDomDocument($domDocument)
            ->removeElementsWithDisplayNone()
            ->removeRedundantClassesAfterCssInlined($cssInliner);

        $finalHtml = CssToAttributeConverter::fromDomDocument($domDocument)
            ->convertCssToVisualAttributes()
            ->renderBodyContent();

        return $this->parsed = (new EmailParser)->parse($this->prefixStaleClasses($finalHtml) ?? '');
    }

    /**
     * Prefix stale classes.
     *
     * Usually when the HTML is parsed via the emogrifier, if the emogrifier is unable to
     * extract the classes CSS style into inline styles, the classes are left as they were in the element
     * however, stale general classes e.q. block, text-left may cause issues with alignment because they
     * already exists as Tailwind CSS classses.
     *
     * @param  string  $html
     * @return string
     */
    protected function prefixStaleClasses($html, string $prefix = 'c_')
    {
        if (empty($html)) {
            return $html;
        }

        $dom = HtmlDomParser::str_get_html($html);

        if (! $dom) {
            return $html;
        }

        foreach ($dom->find('*[class]') as $element) {
            // On some elements, the class is just set to 'true' for some reason,
            // and the parser throws an errors as it's trying to access it as an array offset.
            if (! is_string($element->getAllAttributes()['class'] ?? null)) {
                continue;
            }

            $currentClassAttribute = $element->getAttribute('class');

            if (empty(trim($currentClassAttribute))) {
                continue;
            }

            $classArray = explode(' ', $currentClassAttribute);

            array_walk($classArray, 'trim');

            $newClassAttribute = implode(' ', array_map(fn ($class) => $prefix.$class, $classArray));

            $element->setAttribute('class', $newClassAttribute);
        }

        return $dom->save();
    }
}
