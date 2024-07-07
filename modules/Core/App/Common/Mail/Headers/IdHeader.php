<?php
 

namespace Modules\Core\App\Common\Mail\Headers;

class IdHeader extends Header
{
    /**
     * Get the header value
     *
     * For example Message-ID header can have only one value
     * The method can be used to fetch the ID which contains only 1 value
     *
     * @return string|null
     */
    public function getValue()
    {
        return $this->simpifyHeaders($this->value)[0] ?? null;
    }

    /**
     * Get all header ids
     *
     * @return array
     */
    public function getIds()
    {
        return $this->simpifyHeaders($this->value);
    }

    /**
     * Parse the id header
     *
     * As the ID's are provided e.q. <message-id@gmail.com> we need to
     * remove the < > in order to be accepted as valid ID header
     *
     * @param  string  $headerValue
     * @return string
     */
    protected function createValidIdHeader($headerValue)
    {
        return trim(str_replace(['<', '>'], '', $headerValue));
    }

    /**
     * Simplyfy ID headers e.q. like references
     *
     * Some mail clients e.q. like Outlook add coma (,) instead of space
     * we need to replace the coma with space
     *
     * This is no longer valid but some mail clients are still using this approach
     *
     * @see https://tools.ietf.org/html/rfc822#appendix-C.3.6
     *
     * @param  array|string  $value
     * @return array|string|null
     */
    protected function simpifyHeaders($value)
    {
        if (is_array($value)) {
            // Convert it to string so we can parse both arrays and string and make them valid
            //
            // For example, sometimes the reference can be array
            // but because Outlook messed up in one array value
            // there may be multiple references separated by coma
            // e.q.
            //
            // array (size=1)
            // 0 => '<VI1PR0501MB228640075CF20E3353CD1EAFC0670@VI1PR0501MB2286.eurprd05.prod.outlook.com>,<eed01fb5e7b8851ef4dc2e26bbca25a8@leads.test>'
            $value = implode(' ', $value);
        }

        /**
         * Consider the example below:
         *
         * <CABSor9BMTW+ry8kEpYhB-b1DGNqEvG5p=1u=YBMDJzefAfk-Cw@mail.gmail.com>
         * <AM6PR08MB419797B3B69C1CFEE7F771D7FC6B0@AM6PR08MB4197.eurprd08.prod.outlook.com>,
         * <CABSor9C7hxOTeL74d+HeSZPPJE1RDX7k=mcRUQF4MgBRu6651Q@mail.gmail.com>
         *
         * Match anything between the > closing and the < identifier
         * Any characters between the separators will be replaced with single
         * space so we can parse them
         *
         * e.q. works for:
         *
         * <unique-id@example.com>, <unique-id@example.com>
         * <unique-id@example.com>,<unique-id@example.com>
         * <unique-id@example.com>any-separator <unique-id@example.com>
         * <unique-id@example.com>    <unique-id@example.com>
         * <unique-id@example.com><unique-id@example.com>
         *
         * @var string
         */
        $regex = '/>(.*?)</m';

        $value = explode(' ', preg_replace($regex, '> <', $value));

        return collect($value)->reject(fn ($id) => empty($id))
            ->map(fn ($headerId) => $this->createValidIdHeader($headerId))
            ->values()
            ->all();
    }
}
