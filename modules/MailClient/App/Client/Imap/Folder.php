<?php
 

namespace Modules\MailClient\App\Client\Imap;

use DateInterval;
use DateTime;
use DateTimeImmutable;
use Ddeboer\Imap\Exception\MessageDoesNotExistException;
use Ddeboer\Imap\Search\Date\Since as SinceFilter;
use Ddeboer\Imap\Search\Flag\Seen;
use Ddeboer\Imap\Search\Flag\Unseen;
use Ddeboer\Imap\SearchExpression;
use Modules\MailClient\App\Client\AbstractFolder;
use Modules\MailClient\App\Client\Exceptions\MessageNotFoundException;
use Modules\MailClient\App\Client\FolderIdentifier;
use Modules\MailClient\App\Client\MasksMessages;

class Folder extends AbstractFolder
{
    use MasksMessages;

    /**
     * Get folder uidvalidity
     *
     * NOTE: Do not rely on this value as on some mail servers, for all folders it's the same value
     * In this case, the folder name should be used instead.
     *
     * @return int|null
     */
    public function getId()
    {
        if ($this->isSelectable() &&
            $imapUidValidity = $this->getEntity()->getStatus(SA_UIDVALIDITY)) {
            // Check if the uidvalidity property is set, as e.q. on Outlook this property may not be present
            return $imapUidValidity->uidvalidity ?? null;
        }

        return null;
    }

    /**
     * Get folder messages
     *
     * @return \Illuminate\Support\Collection
     */
    public function getMessages(...$args)
    {
        return $this->maskMessages(
            $this->getEntity()->getMessages(...$args),
            Message::class
        );
    }

    /**
     * Get messages starting from specific date and time
     *
     * @param  string  $dateTime
     * @return \Illuminate\Support\Collection
     */
    public function getMessagesFrom($dateTime)
    {
        return $this->getMessages(
            $this->createSinceFilter($dateTime),
            \SORTDATE, // Sort criteria
            true // Descending order
        );
    }

    /**
     * Get the latest message from a folder
     *
     * @return \Modules\MailClient\App\Client\Imap\Message|null
     */
    public function getLatestMessage()
    {
        $today = new DateTimeImmutable();
        $fiveMinutesAgo = $today->sub(new DateInterval('PT5M'));

        $messages = $this->getMessages(
            $this->createSinceFilter($fiveMinutesAgo),
            \SORTDATE, // Sort criteria
            true // Descending order
        );

        return $messages->first();
    }

    /**
     * Get folder message
     *
     *
     * @param  int  $uid
     * @return \Modules\MailClient\App\Client\Imap\Message
     *
     * @throws \Modules\MailClient\App\Client\Exceptions\MessageNotFoundException
     */
    public function getMessage($uid)
    {
        try {
            return $this->maskMessage(
                $this->getEntity()->getMessage($uid),
                Message::class
            );
        } catch (MessageDoesNotExistException) {
            throw new MessageNotFoundException;
        }
    }

    /**
     * Add a message to the mailbox.
     *
     *
     * @return bool
     */
    public function addMessage(string $message, ?string $options = null)
    {
        return $this->getEntity()->addMessage($message, $options);
    }

    /**
     * Get the folder system name
     *
     * @return string
     */
    public function getName()
    {
        return $this->getEntity()->getName();
    }

    /**
     * Get the folder display name
     *
     * @return string
     */
    public function getDisplayName()
    {
        if (empty($this->getDelimiter())) {
            return $this->getEntity()->getName();
        }

        return last(explode(
            $this->getDelimiter(),
            $this->getEntity()->getName()
        ));
    }

    /**
     * Get the folder delimiter
     *
     * @return string
     */
    public function getDelimiter()
    {
        return $this->getEntity()->getDelimiter();
    }

    /**
     * Check whether the folder is selectable
     *
     * @return bool
     */
    public function isSelectable()
    {
        return ! ($this->getEntity()->getAttributes() & \LATT_NOSELECT);
    }

    /**
     * Get messages since last uid scanned
     *
     * @param  int  $uid
     * @return \Illuminate\Support\Collection
     */
    public function getMessagesSinceLastUid($uid)
    {
        $messages = $this->getEntity()->getMessageSequence($uid + 1 .':*');

        // When specifying a sequence "35:*" with FT_UID, we're telling the server to fetch messages starting with UID 35
        // to the last message in the mailbox. If there are no messages that match this criterion
        // (e.g., the highest UID in the mailbox is 34, or there simply are no messages with UID >= 35),
        // the server might be defaulting to fetching the last available message
        // as a form of "fallback" behavior. This could be an undocumented feature or behavior of the
        // specific IMAP servers, or a nuance in the PHP IMAP extension's handling of such cases.
        // In this case, we will check if the first message is the one from the last uid and will unset.
        if (count($messages) > 0 && $messages[0] === (int) $uid) {
            unset($messages[0]);
        }

        return $this->maskMessages($messages, Message::class);
    }

    /**
     * Get all unseen message UID's
     *
     * @param  string|null  $since
     * @return \Illuminate\Support\Collection
     */
    public function getUnseenIds($since = null)
    {
        $search = new SearchExpression();
        $search->addCondition(new Unseen);

        if ($since) {
            $this->addSinceSearchExpression($search, $since);
        }

        /**
         * We will use the \Ddeboer\Imap\MessageIterator Arrayiterator method
         * getArrayCopy to get the Id's only
         *
         * As if we loop throught the messages the package will
         * lazy load the messages structure
         */
        return collect($this->getEntity()->getMessages($search)->getArrayCopy());
    }

    /**
     * Get all seen message UID's
     *
     * @param  string|null  $since
     * @return \Illuminate\Support\Collection
     */
    public function getSeenIds($since = null)
    {
        $search = new SearchExpression();
        $search->addCondition(new Seen);

        if ($since) {
            $this->addSinceSearchExpression($search, $since);
        }

        /**
         * We will use the \Ddeboer\Imap\MessageIterator Arrayiterator method
         * getArrayCopy to get the Id's only
         *
         * As if we loop throught the messages the package will
         * lazy load the messages structure
         */
        return collect($this->getEntity()->getMessages($search)->getArrayCopy());
    }

    /**
     * Get the last uid from the folder
     *
     * @return int
     */
    public function getLastUid()
    {
        $next = $this->getNextUid();

        // No messages found in the folder
        // the next uid is 1, we don't need to subtract
        // one so in order to provide the last uid
        if ($next === 1) {
            return $next;
        }

        return $next ? $next - 1 : 1;
    }

    /**
     * Get the folder next message uid
     *
     * @return int
     */
    public function getNextUid()
    {
        $status = $this->getEntity()->getStatus();

        // E.q. Outlook does not return the uidnext
        // In this case, we will sort the messages and will get the last message
        // uid, the last messageuid indicates the next uid
        if (! isset($status->uidnext)) {
            $lastMessageUid = $this->getAllUids()->max();

            return $lastMessageUid ? ($lastMessageUid + 1) : 1;
        }

        return $status->uidnext;
    }

    /**
     * Get folder all messages UID's
     *
     * @return \Illuminate\Support\Collection
     */
    public function getAllUids()
    {
        /**
         * We will use the \Ddeboer\Imap\MessageIterator Arrayiterator method
         * getArrayCopy to get the Id's only
         *
         * As if we loop throught the messages the package will
         * lazy load the messages structure
         */

        return collect($this->getEntity()->getMessages()->getArrayCopy());
    }

    /**
     * Get the folder unique identiier
     *
     * @return \Modules\MailClient\App\Client\FolderIdentifier
     */
    public function identifier()
    {
        return new FolderIdentifier('name', $this->getName());
    }

    /**
     * Bulk Set Flag for Messages.
     *
     * @param  string  $flag  \Seen, \Answered, \Flagged, \Deleted, and \Draft
     * @param  array|\Ddeboer\Imap\MessageIterator|string  $numbers  Message numbers
     */
    public function setFlag(string $flag, $numbers)
    {
        return $this->getEntity()->setFlag($flag, $numbers);
    }

    /**
     * Bulk Set Flag for Messages.
     *
     * @param  string  $flag  \Seen, \Answered, \Flagged, \Deleted, and \Draft
     * @param  array|\Ddeboer\Imap\MessageIterator|string  $numbers  Message numbers
     */
    public function clearFlag(string $flag, $numbers)
    {
        return $this->getEntity()->clearFlag($flag, $numbers);
    }

    /**
     * Mask a given message
     *
     * @param  mixed  $message
     * @param  string  $maskIntoClass
     * @return \Modules\MailClient\App\Client\Imap\Message
     */
    protected function maskMessage($message, $maskIntoClass)
    {
        return (new $maskIntoClass($message))->setFolder($this);
    }

    /**
     * Add since search expression
     *
     * @param  \Ddeboer\Imap\SearchExpression  $expression
     * @param  string  $dateTime
     */
    protected function addSinceSearchExpression($expression, $dateTime)
    {
        $expression->addCondition($this->createSinceFilter($dateTime));
    }

    /**
     * Create since search filter
     *
     * @param  string|\DateTimeImmutable  $dateTime
     * @return \Ddeboer\Imap\Search\Date\Since
     */
    protected function createSinceFilter($dateTime)
    {
        return new SinceFilter(
            $dateTime instanceof DateTimeImmutable ? $dateTime : new DateTime($dateTime)
        );
    }
}
