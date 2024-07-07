<?php
 

namespace Modules\MailClient\App\Client\Outlook;

use GuzzleHttp\Client;
use Illuminate\Support\Str;
use Microsoft\Graph\Model\UploadSession;
use Modules\Core\App\Common\Microsoft\Services\Batch\BatchPostRequest;
use Modules\Core\App\Common\Microsoft\Services\Batch\BatchRequests;
use Modules\Core\App\Facades\MsGraph as Api;

trait InteractsWithAttachments
{
    /**
     * The maximum request file size in bytes
     *
     * @var int
     */
    protected $maxRequestFileSize = 3145728; // 3 MB = 3145728 Bytes (in binary)

    /**
     * Attachments build cache
     *
     * @var array|null
     */
    protected $attachmentsBuild = null;

    /**
     * Build the message attachments array
     *
     * @return array
     */
    protected function buildAttachments()
    {
        if ($this->attachmentsBuild) {
            return $this->attachmentsBuild;
        }

        $attachments = [];

        foreach (['attachments', 'rawAttachments'] as $type) {
            foreach ($this->{$type} as $attachment) {
                $method = $type === 'attachments' ? 'prepAttachment' : 'prepAttachmentData';
                $attachments[] = $this->{$method}($attachment, $attachment['options']);
            }
        }

        return $this->attachmentsBuild = $attachments;
    }

    /**
     * Group the attachments in "upload" groups based on their size
     *
     * @return array
     */
    protected function createAttachmentsUploadGroups()
    {
        return collect($this->buildAttachments())->mapToGroups(function ($attachment) {
            if ($attachment['size'] < $this->maxRequestFileSize) {
                return ['attachments' => $attachment];
            }

            return ['session' => $attachment];
        })->all();
    }

    /**
     * Perform upload for the attachments based on their groups
     *
     * @param  string  $messageId
     * @return void
     */
    protected function performGroupsUpload($messageId)
    {
        $groups = $this->createAttachmentsUploadGroups();
        $this->attachLargeFiles($groups['session'] ?? [], $messageId);
        $this->attachFiles($groups['attachments'] ?? [], $messageId);
    }

    /**
     * Prepare the given attachment.
     *
     * @param  array  $attachment
     * @param  array  $options
     * @return array
     */
    protected function prepAttachment($attachment, $options = [])
    {
        $contents = file_get_contents($attachment['file']);

        $fileAttachment = [
            '@odata.type' => '#microsoft.graph.fileAttachment',
            'contentBytes' => base64_encode($contents),
            'name' => basename($attachment['file']),
            'size' => mb_strlen($contents, '8bit'),
        ];

        return $this->prepAttachmentOptions($fileAttachment, $options);
    }

    /**
     * Prepare the given data attachment.
     *
     * @param  array  $attachment
     * @param  array  $options
     * @return array
     */
    protected function prepAttachmentData($attachment, $options = [])
    {
        $raw = Str::isBase64Encoded($attachment['data']) ? base64_decode($attachment['data']) : $attachment['data'];

        $fileAttachment = [
            '@odata.type' => '#microsoft.graph.fileAttachment',
            'contentBytes' => Str::isBase64Encoded($attachment['data'])
                    ? $attachment['data']
                    : base64_encode($attachment['data']),
            'name' => $attachment['name'],
            'size' => mb_strlen($raw, '8bit'),
        ];

        return $this->prepAttachmentOptions($fileAttachment, $options);
    }

    /**
     * Prepare the attachment options
     *
     * @param  array  $attachment
     * @param  array  $options
     * @return array
     */
    protected function prepAttachmentOptions($attachment, $options = [])
    {
        // First we will check for a MIME type on the message, which instructs the
        // mail client on what type of attachment the file is so that it may be
        // downloaded correctly by the user. The MIME option is not required.
        if (isset($options['mime'])) {
            $attachment['contentType'] = $options['mime'];
        }

        // Check if attachment has content ID
        // In most cases this will be used for embedded images
        if (isset($options['contentId'])) {
            $attachment['contentId'] = $options['contentId'];

            // If content ID is set, we can pass the isInline too
            if (isset($options['isInline'])) {
                $attachment['isInline'] = $options['isInline'];
            }
        }

        // If an alternative name was given as an option, we will set that on this
        // attachment so that it will be downloaded with the desired names from
        // the developer, otherwise the default file names will get assigned.
        if (isset($options['as'])) {
            $attachment['name'] = $options['as'];
        }

        return $attachment;
    }

    /**
     * Get the message attachment URL
     *
     * @param  string  $messageId
     * @param  string  $extra
     * @return string
     */
    protected function getAttachmentsUri($messageId, $extra = '')
    {
        return '/me/messages/'.$messageId.'/attachments'.($extra ? ('/'.$extra) : '');
    }

    /**
     * Get the given message attachments
     *
     * @param  string  $messageId
     * @return array
     */
    protected function getAttachments($messageId)
    {
        return Api::createGetRequest($this->getAttachmentsUri($messageId))->execute()->getBody();
    }

    /**
     * Attach single file with size less then 3MB to the given message
     *
     * @param  array  $attachment
     * @param  string  $messageId
     * @return void
     */
    protected function attachFile($attachment, $messageId)
    {
        return Api::createPostRequest('/me/messages/'.$messageId.'/attachments', $attachment)->execute();
    }

    /**
     * Attach files with size less then 3MB to the given message
     *
     * @param  array  $attachments
     * @param  string  $messageId
     * @return void
     */
    protected function attachFiles($attachments, $messageId)
    {
        $batch = new BatchRequests;

        foreach ($attachments as $attachment) {
            $batch->push(BatchPostRequest::make($this->getAttachmentsUri($messageId), $attachment));
        }

        Api::createBatchRequest($batch)->execute();
    }

    /**
     * Attach large file (3MB-150MB) to the given message
     *
     * @see https://docs.microsoft.com/en-us/graph/outlook-large-attachments
     *
     * @param  array  $attachment
     * @param  string  $messageId
     * @return void
     */
    protected function attachLargeFile($attachment, $messageId)
    {
        // Use Guzzle as the Microsoft API is throwin error when using the API Client
        // as the API already includes the auth token but it should not be passed as recommended
        // in the docs because the upload url already contains the authentication token
        $guzzle = new Client;

        $uploadSession = Api::createPostRequest('/me/messages/'.$messageId.'/attachments/createUploadSession', [
            'AttachmentItem' => [
                'attachmentType' => 'file',
                'name' => $attachment['name'],
                'size' => $attachment['size'],
            ],
        ])
            ->setReturnType(UploadSession::class)
            ->execute();

        $fragSize = 1024 * 1024 * 4;

        $fileSize = $attachment['size'];
        $numFragments = ceil($fileSize / $fragSize);
        $bytesRemaining = $fileSize;

        $i = 0;
        while ($i < $numFragments) {
            $chunkSize = $numBytes = $fragSize;
            $start = $i * $fragSize;
            $end = $i * $fragSize + $chunkSize - 1;
            $offset = $i * $fragSize;

            if ($bytesRemaining < $chunkSize) {
                $chunkSize = $numBytes = $bytesRemaining;
                $end = $fileSize - 1;
            }

            // Create resource from the file contents
            $stream = fopen('php://memory', 'r+');
            fwrite($stream, base64_decode($attachment['contentBytes']));
            rewind($stream);

            $fileBodyPart = stream_get_contents($stream, $chunkSize, $offset);
            fclose($stream);

            $guzzle->put($uploadSession->getUploadUrl(), [
                'headers' => [
                    'Content-Length' => $numBytes,
                    'Content-Range' => 'bytes '.$start.'-'.$end.'/'.$fileSize,
                ],
                'body' => $fileBodyPart,
            ]);

            $bytesRemaining = $bytesRemaining - $chunkSize;
            $i++;
        }

        $guzzle->delete($uploadSession->getUploadUrl());
    }

    /**
     * Attach large files (3MB-150MB) to the given message
     *
     * @param  array  $attachments
     * @param  string  $messageId
     * @return void
     */
    protected function attachLargeFiles($attachments, $messageId)
    {
        foreach ($attachments as $attachment) {
            $this->attachLargeFile($attachment, $messageId);
        }
    }

    /**
     * Check whether the attachments should be uploaded with session
     *
     * @return bool
     */
    protected function shouldUploadWithSession()
    {
        $totalSize = collect($this->buildAttachments())->pluck('size')->sum();

        return $totalSize >= $this->maxRequestFileSize;
    }
}
