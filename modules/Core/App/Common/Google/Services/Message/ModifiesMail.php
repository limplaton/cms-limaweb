<?php
 

namespace Modules\Core\App\Common\Google\Services\Message;

use Google\Service\Gmail\ModifyMessageRequest;

trait ModifiesMail
{
    /**
     * Marks emails as "READ". Returns string of message if fail
     *
     * @throws \Google\Service\Exception
     */
    public function markAsRead(): Mail
    {
        return $this->removeLabel('UNREAD');
    }

    /**
     * Marks emails as unread.
     *
     * @throws \Google\Service\Exception
     */
    public function markAsUnread(): Mail
    {
        return $this->addLabel('UNREAD');
    }

    /**
     * @throws \Google\Service\Exception
     */
    public function markAsImportant(): Mail
    {
        return $this->addLabel('IMPORTANT');
    }

    /**
     * @throws \Google\Service\Exception
     */
    public function markAsNotImportant(): Mail
    {
        return $this->removeLabel('IMPORTANT');
    }

    /**
     * @throws \Google\Service\Exception
     */
    public function addStar(): Mail
    {
        return $this->addLabel('STARRED');
    }

    /**
     * @throws \Google\Service\Exception
     */
    public function removeStar(): Mail
    {
        return $this->removeLabel('STARRED');
    }

    /**
     * Send the email to the trash
     *
     * @throws \Google\Service\Exception
     */
    public function sendToTrash(): Mail
    {
        return $this->addLabel('TRASH');
    }

    /**
     * Remove message from trash.
     *
     * @throws \Google\Service\Exception
     */
    public function removeFromTrash(): Mail
    {
        return $this->removeLabel('TRASH');
    }

    /**
     * Adds labels to the email.
     *
     * @throws \Google\Service\Exception
     */
    public function addLabel(array|string $labels): Mail
    {
        if (is_string($labels)) {
            $labels = [$labels];
        }

        $request = new ModifyMessageRequest;
        $request->setAddLabelIds($labels);

        return $this->modify($request);
    }

    /**
     * Removes labels from the email.
     *
     * @throws \Google\Service\Exception
     */
    public function removeLabel(array|string $labels): Mail
    {
        if (is_string($labels)) {
            $labels = [$labels];
        }

        $request = new ModifyMessageRequest;
        $request->setRemoveLabelIds($labels);

        return $this->modify($request);
    }

    /**
     * Execute the modify message request.
     */
    protected function modify(ModifyMessageRequest $request): Mail
    {
        $message = $this->service->users_messages->modify('me', $this->getId(), $request);

        return new Mail($this->client, $message);
    }
}
