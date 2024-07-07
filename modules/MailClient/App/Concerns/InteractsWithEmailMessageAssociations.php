<?php
 

namespace Modules\MailClient\App\Concerns;

use Modules\Core\App\Resource\Resource;
use Modules\MailClient\App\Client\Compose\AbstractComposer;
use Modules\MailClient\App\Client\Contracts\MessageInterface;
use Modules\MailClient\App\Models\EmailAccountMessage;

trait InteractsWithEmailMessageAssociations
{
    /**
     * Associations separator
     */
    protected string $associationsSeparator = ';';

    /**
     * When sending a message, we need to add the associations uuids as headers to the message
     */
    protected function addComposerAssociationsHeaders(AbstractComposer $composer, array $associations): void
    {
        foreach (EmailAccountMessage::resource()->associateableResources() as $resource) {
            if (is_array($associations[$resource->name()] ?? null)) {
                if ($relatedModels = $this->getRelatedResourceModels($resource, $associations[$resource->name()])) {
                    $composer->addHeader(
                        $this->createAssociationHeaderName($resource->name()),
                        implode($this->associationsSeparator, $relatedModels->pluck('uuid')->all())
                    );
                }
            }
        }
    }

    /**
     * Sync the message associations via the header
     */
    protected function syncAssociationsViaMessageHeaders(EmailAccountMessage $dbMessage, MessageInterface $remoteMessage): void
    {
        foreach (EmailAccountMessage::resource()->associateableResources() as $relation => $resource) {
            if ($header = $remoteMessage->getHeader($this->createAssociationHeaderName($resource->name()))) {
                if (empty($header->getValue())) {
                    continue;
                }

                $dbMessage->{$relation}()->syncWithoutDetaching(
                    $this->getAssociatedModelsViaHeaderUuids($resource, $header->getValue())
                );
            }
        }
    }

    /**
     * Get the associate models via the uuids
     *
     * @param  string  $uuids  The header value uuids
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function getAssociatedModelsViaHeaderUuids(Resource $resource, string $uuids)
    {
        $uuids = explode($this->associationsSeparator, $uuids);

        return $resource->newModel()->whereIn('uuid', $uuids)->get();
    }

    /**
     * Create associations header name for a given resource
     */
    protected function createAssociationHeaderName(string $resourceName): string
    {
        return 'X--'.ucfirst($resourceName).'-Assoc';
    }

    /**
     * Get the related resource models
     *
     * @return \Illuminate\Support\Collection
     */
    protected function getRelatedResourceModels(Resource $resource, array $associations)
    {
        if (count($associations) === 0) {
            return false;
        }

        return $resource->newModel()->findMany($associations);
    }
}
