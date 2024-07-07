<?php
 

namespace Modules\Core\App\Common\Google\Services\Message;

use Illuminate\Support\Collection;

trait ProvidesMailAttachments
{
    /**
     * @var \Illuminate\Support\Collection
     */
    protected $attachments;

    /**
     * Check whether the message has attachments
     *
     * @return bool
     */
    public function hasAttachments()
    {
        return ! $this->getAttachments()->isEmpty();
    }

    /**
     * Number of attachments of the message.
     *
     * @return int
     */
    public function countAttachments()
    {
        return $this->getAttachments()->count();
    }

    /**
     * Get the message attachments
     *
     * @return \Illuminate\Support\Collection
     */
    public function getAttachments()
    {
        if (! is_null($this->attachments)) {
            return $this->attachments;
        }

        $attachments = new Collection;
        $parts = $this->getAllParts($this->parts);

        foreach ($parts as $part) {
            if (! empty($part->body->attachmentId)) {
                $attachments->push(new Attachment($this->client, $this->getId(), $part));
            }
        }

        return $this->attachments = $attachments;
    }
}
