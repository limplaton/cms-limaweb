<?php
 

namespace Modules\MailClient\App\Client\Outlook;

/**
 * Microsoft does not allow setting custom headers on sent messages
 * Also it does not allow fetching the in-reply-to and references and other headers from sent messages
 *
 * @see  https://github.com/microsoftgraph/microsoft-graph-docs/issues/2716
 *
 * We will use the extended properties from the API to stora and gather this data
 * e.q. when sending message when we add custom headers, they won't be visible in the email
 * but the application can use them as headers for any actions
 *
 * Technically, these headers will be for internal usage
 *
 * Fetching such headers is only available when fetching single message, or messages list
 * this feaure does not work when fetching via delta
 */
class HeadersMap
{
    /**
     * Map the PID with the actual header name
     *
     * @see  https://docs.microsoft.com/en-us/office/client-developer/outlook/mapi/mapping-canonical-property-names-to-mapi-names
     */
    const MAP = [
        /**
         * @see https://docs.microsoft.com/en-us/openspecs/exchange_server_protocols/ms-oxcmail/a6709bdd-89f0-4249-b4ad-cf72a9a5eeda
         */
        'in-reply-to' => 'String 0x1042',

        /**
         * @see https://docs.microsoft.com/en-us/openspecs/exchange_server_protocols/ms-oxcmail/bbb98505-0f28-433e-9dd9-bfe7e610b6dd
         */
        'references' => 'String 0x1039',
    ];
}
