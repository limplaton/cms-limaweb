<?php
 

namespace Modules\Calls\App\VoIP;

use BadMethodCallException;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Str;
use JsonSerializable;

class Call implements Arrayable, JsonSerializable
{
    /**
     * Initialize new call
     *
     * @param  string  $primaryNumber
     * @param  string  $from
     * @param  string  $to
     * @param  string  $status
     */
    public function __construct(protected $primaryNumber, protected $from, protected $to, protected $status)
    {
    }

    /**
     * Possible call statuses
     *
     * @var array
     */
    protected $statuses = [
        'queued',
        'initiated',
        'ringing',
        'in-progress',
        'completed',
        'busy',
        'no-answer',
        'canceled',
        'failed',
    ];

    /**
     * Check whether the call is missed
     */
    public function isMissed(): bool
    {
        return $this->isNoAnswer();
    }

    /**
     * Check whether the call is incoming and missed
     */
    public function isMissedIncoming(): bool
    {
        return $this->isMissed() && $this->isIncoming();
    }

    /**
     * Check whether the call is incoming
     */
    public function isIncoming(): bool
    {
        return strcmp(
            str_replace(' ', '', $this->primaryNumber),
            str_replace(' ', '', $this->to)
        ) === 0;
    }

    /**
     * Check whether the call has the given status
     *
     * @param  string  $status
     */
    public function isStatus($status): bool
    {
        return $this->status === $status && in_array($status, $this->statuses);
    }

    /**
     * toArray
     *
     * @return array
     */
    public function toArray()
    {
        return [
            'from' => $this->from,
            'to' => $this->to,
            'status' => $this->status,
            'is_incoming' => $this->isIncoming(),
        ];
    }

    /**
     * jsonSerialize
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Dynamic method call
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        if (str_starts_with($method, 'is')) {
            return $this->isStatus(Str::after(Str::snake($method, '-'), 'is-'));
        }

        throw new BadMethodCallException(sprintf(
            'Call to undefined method %s::%s()',
            static::class,
            $method
        ));
    }
}
