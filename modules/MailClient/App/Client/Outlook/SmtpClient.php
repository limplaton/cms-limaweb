<?php
 

namespace Modules\MailClient\App\Client\Outlook;

use Illuminate\Support\Str;
use Microsoft\Graph\Model\BodyType;
use Microsoft\Graph\Model\OpenTypeExtension;
use Modules\Core\App\Common\Mail\EmbeddedImagesProcessor;
use Modules\Core\App\Common\Microsoft\Services\Batch\BatchDeleteRequest;
use Modules\Core\App\Common\Microsoft\Services\Batch\BatchPostRequest;
use Modules\Core\App\Common\Microsoft\Services\Batch\BatchRequests;
use Modules\Core\App\Common\OAuth\AccessTokenProvider;
use Modules\Core\App\Facades\MsGraph as Api;
use Modules\MailClient\App\Client\AbstractSmtpClient;
use Modules\MailClient\App\Client\Contracts\SupportSaveToSentFolderParameter;
use Modules\MailClient\App\Client\FolderIdentifier;

class SmtpClient extends AbstractSmtpClient implements SupportSaveToSentFolderParameter
{
    use InteractsWithAttachments;

    /**
     * @see  https://docs.microsoft.com/en-us/openspecs/exchange_server_protocols/ms-oxcmail/3e9b31be-16d4-4660-a6ff-431373fb54fd
     */
    const PS_INTERNET_HEADERS_NAMESPACE = 'String {00020386-0000-0000-C000-000000000046} Name';

    /**
     * @see  https://docs.microsoft.com/en-us/graph/extensibility-open-users
     */
    const OPEN_EXTENSION_HEADERS_ID = 'Innoclapps.MailClient.Headers';

    /**
     * Indicates whether to sent the message in the sent folder
     * Only applies on new mails, not reply to mail
     *
     * @var bool
     */
    protected $saveToSentFolder = true;

    /**
     * Initialize new SmtpClient instance.
     */
    public function __construct(protected AccessTokenProvider $token)
    {
        Api::connectUsing($token);
    }

    /**
     * Send mail message
     *
     * @return null|\Modules\MailClient\App\Client\Contracts\MessageInterface
     */
    public function send()
    {
        $payload = $this->prepareNewMessage();

        if ($this->shouldUploadWithSession()) {
            $draftId = Api::createPostRequest('/me/messages', $payload)
                ->execute()
                ->getBody()['id'];

            $this->performGroupsUpload($draftId);
            // Will be saved in SentItems in all cases
            $this->sendMessage($draftId);
        } else {
            Api::createPostRequest('/me/sendMail', [
                'Message' => array_merge($payload, ['attachments' => $this->buildAttachments()]),
                'saveToSentItems' => $this->saveToSentFolder,
            ])->execute();
        }

        if ($this->saveToSentFolder) {
            return $this->imap->getLatestSentMessageAndStrictCompare(
                $payload['subject'],
                $this->token->getEmail(),
                $this->to,
                $payload['internetMessageId']
            );
        }
    }

    /**
     * Reply to a given mail message
     *
     * @param  string  $remoteId
     * @return null|\Modules\MailClient\App\Client\Contracts\MessageInterface
     */
    public function reply($remoteId, ?FolderIdentifier $folder = null)
    {
        $message = $this->imap->getMessage($remoteId);
        $payload = $this->prepareNewMessage($message);

        // When there is no subject set, we will just
        // create a reply subject from the original message
        if (! $this->subject) {
            $payload['subject'] = $this->createReplySubject($message->getSubject());
        }

        $draftId = $this->createDraftReplyMessage($message, $payload);

        // Cannot make batch request post, throwing some error
        // because Content-Type header is not provided, but actually provided
        // For this reason, we will use regular request to send the message
        $this->sendMessage($draftId);

        return $this->imap->getLatestSentMessageAndStrictCompare(
            $payload['subject'],
            $this->token->getEmail(),
            $this->to,
            $payload['internetMessageId']
        );
    }

    /**
     * Forward the given mail message
     *
     * @param  string  $remoteId
     * @return null|\Modules\MailClient\App\Client\Contracts\MessageInterface
     */
    public function forward($remoteId, ?FolderIdentifier $folder = null)
    {
        $message = $this->imap->getMessage($remoteId);
        $payload = $this->prepareNewMessage($message);

        // When there is no subject set, we will just
        // create a reply subject from the original message
        if (! $this->subject) {
            $payload['subject'] = $this->createForwardSubject($message->getSubject());
        }

        $draftId = $this->createDraftForwardMessage($message, $payload);

        // Cannot make batch request post, throwing some error
        // because Content-Type header is not provided, but actually provided
        // For this reason, we will use regular request to send the message
        $this->sendMessage($draftId);

        return $this->imap->getLatestSentMessageAndStrictCompare(
            $payload['subject'],
            $this->token->getEmail(),
            $this->to,
            $payload['internetMessageId']
        );
    }

    /**
     * Indicates whether the message should be saved to the sent folder after it's sent
     * In most cases, this is valid for new mails not for replies
     *
     * @param  bool  $value
     * @return static
     */
    public function saveToSentFolder($value)
    {
        $this->saveToSentFolder = $value;

        return $this;
    }

    /**
     * Prepares the the message payload
     *
     * @param  \Modules\MailClient\App\Client\Outlook\Message|null  $repliesTo
     * @return array
     */
    protected function prepareNewMessage($repliesTo = null)
    {
        $defaults = [
            // We will be generating custom internetMessageId so we can compare it while fething the latest sent message
            'internetMessageId' => '<'.$this->getMessageId().'>',
            'subject' => $this->subject,
            'body' => [
                'contentType' => $this->isHtmlContentType() ? BodyType::HTML : BodyType::TEXT,
                'content' => $this->parseBodyContentWithEmbeddedImages(),
            ],
        ];

        if ($repliesTo) {
            $defaults['conversationId'] = $repliesTo->getConversationId();
            // $defaults['body']['content'] .= $this->createQuoteOfPreviousMessage($repliesTo, $callback);
        }

        $mailBody = array_merge($defaults, $this->buildRecipients());

        // We store the custom headers in openExtensions so we can retrieve them
        // but additionally, we are storing the custom headers as singleValueExtendedProperties
        // which directly inject them as headers via the PS_INTERNET_HEADERS_NAMESPACE
        // This is applied, just the headers to exists in the sent message
        // Later, we will be only using open extensions to retireve the header values
        // @see https://stackoverflow.com/questions/38214197/set-a-custom-header-with-outlook-office-365-rest
        if (! is_null($repliesTo)) {
            $mailBody['singleValueExtendedProperties'] = $this->createHeadersForSingleValueProperty();
        } elseif (count($this->headers) > 0) {
            // For new messages, Microsoft accept the headers
            $mailBody['internetMessageHeaders'] = $this->headers;
        }

        // Set reply to headers only if exists
        // As Outlook is not accepting the same reply-to header as the from/sender
        if (count($this->replyTo) > 0) {
            $mailBody['replyTo'] = [];

            foreach ($this->replyTo as $recipient) {
                $mailBody['replyTo'][] = $this->createAddress($recipient['address'], $recipient['name']);
            }
        }

        // Microsoft requires if sending the from option header both address and name to be configured, if the FROM header
        // is not sent with the request, the default from the Microsoft account will be used

        if ($this->getFromName() && $this->getFromAddress()) {
            /**
             * The mailbox owner and sender of the message.
             * The value must correspond to the actual mailbox used.
             *
             * @link https://docs.microsoft.com/en-us/graph/outlook-create-send-messages#setting-the-from-and-sender-properties
             *
             * NOTE, this does not work, not sure but Microsoft is not changing the header for some reason
             */
            $mailBody['from'] = $this->createAddress($this->getFromAddress(), $this->getFromName());
        }

        return $mailBody;
    }

    /**
     * Parse the body contents with embedded images
     *
     * @return string
     */
    protected function parseBodyContentWithEmbeddedImages()
    {
        // Outlook supports only providing one body type
        // In this case, if no HTML is set, we will pass the text body
        return tap((new EmbeddedImagesProcessor)(
            $this->isHtmlContentType() ? $this->htmlBody : $this->textBody,
            function ($data, $name, $contentType) {
                $contentId = Str::random(10);

                $this->attachData($data, $name, [
                    'mime' => $contentType,
                    'contentId' => $contentId,
                    'isInline' => true,
                ]);

                // For quote replacer
                return "cid:$contentId";
            }
        ), function () {
            $this->attachmentsBuild = null;
        });
    }

    /**
     * Create draft reply message from the given message
     *
     * @param  \Modules\MailClient\App\Client\Outlook\Message  $message
     * @param  array  $payload  The message data
     * @return string
     */
    protected function createDraftReplyMessage($message, $payload)
    {
        $draftId = Api::createPostRequest('/me/messages/'.$message->getId().'/createReply', [
            'message' => $payload,
        ])
            ->execute()
            ->getBody()['id'];

        $this->cleanDraftAttachmentsAndAddHeaders($draftId);
        $this->performGroupsUpload($draftId);

        return $draftId;
    }

    /**
     * Create draft forward message from the given message
     *
     * @param  \Modules\MailClient\App\Client\Outlook\Message  $message
     * @param  array  $payload  The message data
     * @return string
     */
    protected function createDraftForwardMessage($message, $payload)
    {
        $payload['attachments'] = [];
        $payload['hasAttachments'] = false;

        $draftId = Api::createPostRequest('/me/messages/'.$message->getId().'/createForward', [
            'message' => $payload,
        ])
            ->execute()
            ->getBody()['id'];

        $this->cleanDraftAttachmentsAndAddHeaders($draftId);
        $this->performGroupsUpload($draftId);

        return $draftId;
    }

    /**
     * Clean the draft attachments and add the headers as open extension
     *
     * @param  string  $messageId
     * @return void
     */
    protected function cleanDraftAttachmentsAndAddHeaders($messageId)
    {
        $batch = new BatchRequests;

        // When creating reply/forward via the createReply/createForward endpoint
        // Outlook will create and attach all the attachments from the message we are creating
        // In this case, we need to remove those attachments as we are embedding the attachments with our custom logic
        // e.q. giving the user ability to remove/add new attachments
        foreach ($this->getAttachments($messageId)['value'] as $key => $attachment) {
            // When there is no dependsOn, Microsoft is throwing some errors
            // "ConcurrentItemSave"
            // "Conflicts occurred during saving data to store (saveresult: IrresolvableConflict, properties: ). Please retry the request."
            // probablt the requests are overlapping which is causing this conflict, when using depends on, works good, no overlaps
            $requestId = (string) $key;
            if ($key > 0) {
                $lastRequestId = (string) ($key - 1);
            }

            $batch->push(
                BatchDeleteRequest::make(
                    $this->getAttachmentsUri($messageId, $attachment['id'])
                )->setId($requestId)->setDependsOn(isset($lastRequestId) ? [$lastRequestId] : [])
            );
        }

        /**
         * Because Microsoft is not giving an ability to get all the message headers for certain messages like replied
         * we will store in the open type extensions so we can map them later when fetching the headers
         */
        if ($extension = $this->createHeadersAsOpenTypeExtension()) {
            $request = BatchPostRequest::make(
                '/me/messages/'.$messageId.'/extensions',
                $extension
            )->setId('extensions');

            if (isset($lastRequestId)) {
                $request->setDependsOn([$requestId]); // $requestId will be the last dependsOn
            }

            $batch->push($request);
        }

        Api::createBatchRequest($batch)->execute();
    }

    /**
     * Build the message recipients array
     *
     * @return array
     */
    protected function buildRecipients()
    {
        $recipients = [];

        foreach (['to', 'cc', 'bcc'] as $type) {
            $recipients[$type.'Recipients'] = [];

            foreach ($this->{$type} as $address) {
                $recipients[$type.'Recipients'][] = $this->createAddress($address['address'], $address['name']);
            }
        }

        return $recipients;
    }

    /**
     * Send the given draft message
     *
     * @param  string  $id
     * @return mixed?
     */
    protected function sendMessage($id)
    {
        return Api::createPostRequest('/me/messages/'.$id.'/send')->execute();
    }

    /**
     * Create headers for the single value property
     *
     * @return array
     */
    protected function createHeadersForSingleValueProperty()
    {
        $singleValueHeders = [];

        foreach ($this->headers as $header) {
            $singleValueHeders[] = [
                'id' => static::PS_INTERNET_HEADERS_NAMESPACE.' '.$header['name'],
                'value' => $header['value'],
            ];
        }

        return $singleValueHeders;
    }

    /**
     * Create address array
     *
     * @param  string  $address
     * @param  string|null  $name
     * @return array
     */
    protected function createAddress($address, $name)
    {
        return ['emailAddress' => [
            'address' => $address,
            'name' => $name,
        ]];
    }

    /**
     * Create open extension from headers
     *
     * @return bool|\Microsoft\Graph\Model\OpenTypeExtension
     */
    protected function createHeadersAsOpenTypeExtension()
    {
        if (count($this->headers) === 0) {
            return false;
        }

        $values = collect($this->headers)->mapWithKeys(function (array $header) {
            return [$header['name'] => $header['value']];
        })->all();

        return (new OpenTypeExtension($values))->setExtensionName(static::OPEN_EXTENSION_HEADERS_ID);
    }
}
