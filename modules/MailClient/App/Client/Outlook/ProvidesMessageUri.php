<?php
 

namespace Modules\MailClient\App\Client\Outlook;

trait ProvidesMessageUri
{
    /**
     * Get the message uri for the request
     *
     * @param  string  $id
     * @return string
     */
    protected function getMessageUri($id)
    {
        $uri = '/me/messages/'.$id;
        $defaultParams = $this->getDefaultParams();
        $defaultParams['$expand'] .= ', '.$this->createQueryStringMapForCustomHeaders();

        $uri .= '?'.http_build_query($defaultParams);

        return $uri;
    }

    /**
     * Get the messages uri for the request
     *
     * @param  array  $params
     * @return string
     */
    protected function getMessagesUri($params = [])
    {
        $uri = '/me/messages/';

        $query = array_merge($this->getDefaultParams(), $params);
        $query['$expand'] .= ', '.$this->createQueryStringMapForCustomHeaders();

        $uri .= '?'.http_build_query($query);

        return $uri;
    }

    /**
     * Get messages uri for a given folder
     *
     * @param  string  $folderId
     * @param  array  $params
     * @return string
     */
    protected function getFolderMessagesUri($folderId, $params = [])
    {
        $uri = "/me/mailFolders/$folderId/messages";

        $query = array_merge($this->getDefaultParams(), $params);
        $query['$expand'] .= ', '.$this->createQueryStringMapForCustomHeaders();

        $uri .= '?'.http_build_query($query);

        return $uri;
    }

    /**
     * Get the messages delta uri for the request for a given folder
     *
     * @param  string  $folderId
     * @return string
     */
    protected function getMessagesDeltaUri($folderId)
    {
        // Only select id, as we will use batch to fetch all the messages data
        $uri = "/me/mailFolders/{$folderId}/messages/delta";

        $uri .= '?'.http_build_query(['$select' => 'id']);

        return $uri;
    }

    /**
     * Get the open type extensions expand query string
     *
     * @return string
     */
    protected function getOpenExtensionsExpandQueryString()
    {
        $id = 'Microsoft.OutlookServices.OpenTypeExtension.'.SmtpClient::OPEN_EXTENSION_HEADERS_ID;

        return "extensions(\$filter=id eq '$id')";
    }

    /**
     * The message request default params
     *
     * @return array
     */
    protected function getDefaultParams()
    {
        return [
            '$select' => $this->getMessageSelectParam(),
            '$expand' => 'attachments, '.$this->getOpenExtensionsExpandQueryString(),
        ];
    }

    /**
     * Create map for custom header
     *
     * @return string
     */
    protected function createQueryStringMapForCustomHeaders()
    {
        $singleValueExtendedProperties = '$filter=id';
        $i = 0;

        foreach (HeadersMap::MAP as $name => $id) {
            if ($i === 0) {
                $singleValueExtendedProperties .= " eq '$id'";
            } else {
                $singleValueExtendedProperties .= " or id eq '$id'";
            }
            $i++;
        }

        return 'singleValueExtendedProperties('.$singleValueExtendedProperties.')';
    }

    /**
     * The message request select params
     *
     * @return string
     */
    protected function getMessageSelectParam()
    {
        $select = [
            'lastModifiedDateTime',
            'receivedDateTime',
            'sentDateTime',
            'internetMessageId',
            'subject',
            'parentFolderId',
            'isRead',
            'isDraft',
            'body',
            'sender',
            'from',
            'toRecipients',
            'ccRecipients',
            'bccRecipients',
            'replyTo',
            'conversationId',
            // Returned only on applying a $select query option. Read-only.
            'internetMessageHeaders',
        ];

        return implode(',', $select);
    }
}
