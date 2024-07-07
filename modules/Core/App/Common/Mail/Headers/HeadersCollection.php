<?php
 

namespace Modules\Core\App\Common\Mail\Headers;

use Illuminate\Support\Collection;

class HeadersCollection extends Collection
{
    /**
     * The generic header class
     *
     * @var string
     */
    protected $genericHeader = Header::class;

    /**
     * The headers that contains an id
     *
     * @var array
     */
    protected $headerMaps = [
        IdHeader::class => [
            'message-id',
            'content-id',
            'in-reply-to',
            'references',
        ],
        AddressHeader::class => [
            'from',
            'to',
            'cc',
            'bcc',
            'reply-to',
            'sender',
        ],
        DateHeader::class => [
            'date',
            'resentdate',
            'deliverydate',
            'expires',
            'expirydate',
            'replyby',
        ],
    ];

    /**
     * Find a header by name
     */
    public function find(string $name): ?Header
    {
        return $this->first(fn ($header) => strtolower($header->getName()) === strtolower($name));
    }

    /**
     * Push header to the collection
     *
     * @param  string  $name
     * @param  string|null  $value
     * @return static
     */
    public function pushHeader($name, $value)
    {
        $class = $this->getClassFor($name);

        $this->push(new $class($name, $value));

        return $this;
    }

    /**
     * Returns the name of an header class for the passed header name.
     *
     * @param  string  $name
     * @return string
     */
    protected function getClassFor($name)
    {
        $test = strtolower($name);

        foreach ($this->headerMaps as $class => $matchers) {
            foreach ($matchers as $matcher) {
                if ($test === $matcher) {
                    return $class;
                }
            }
        }

        return $this->genericHeader;
    }
}
