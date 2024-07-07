<?php
 

namespace Modules\MailClient\App\Support;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Modules\Core\App\Support\TreeBuilder;
use Modules\MailClient\App\Client\FolderIdentifier;
use Modules\MailClient\App\Client\FolderType;
use Modules\MailClient\App\Http\Resources\EmailAccountFolderResource;
use Modules\MailClient\App\Models\EmailAccountFolder;

class EmailAccountFolderCollection extends Collection
{
    /**
     * Defines the order for the folders based on their type
     */
    const ORDER_MAP = [
        FolderType::INBOX => 1,
        FolderType::SENT => 2,
        FolderType::ARCHIVE => 3,
        FolderType::DRAFTS => 4,
        FolderType::OTHER => 5,
        FolderType::SPAM => 5000,
        FolderType::TRASH => 6000,
    ];

    /**
     * Create tree from all folders
     */
    public function createTree(Request $request): array
    {
        return (new TreeBuilder)->build($this->createArrayFromResource($request));
    }

    /**
     * Create active folders tree from the folders collection
     */
    public function createTreeFromActive(Request $request): array
    {
        $folders = $this->createArrayFromResource($request);

        // From the all folders array, create collection and
        // filter only the syncable/active folders
        $folderTree = (new static($folders))->where('syncable', true)->map(function ($folder) {
            if (! empty($folder['parent_id'])) {
                // Find his parent from all folders collection and check if it's syncable/active
                // If the parent folder of this folder is not syncable the
                // TreeBuilder won't be able to identify the parent folder because
                // here we are looping through only the active/syncable
                // In this case, the child folders which parent is not syncable
                // will float as standalone folders
                $parentFolder = $this->firstWhere('id', $folder['parent_id']);

                if ($parentFolder && $parentFolder->syncable === false) {
                    // Update the parent key so there won't be parent for this folder
                    $folder['parent_id'] = null;
                }
            }

            return $folder;
        })->all();

        return (new TreeBuilder())->build($folderTree);
    }

    /**
     * Get only the active folders
     *
     * @return static
     */
    public function active()
    {
        return $this->where('syncable', true)->values();
    }

    /**
     * Create data for the front-end from resource resource from the folders collection
     *
     * Useful for creating tree and we need the tree data to be the same like
     * the JSON resource, in this case, if we ever change the resource, the
     * tree will be updated too
     */
    protected function createArrayFromResource(Request $request): array
    {
        return EmailAccountFolderResource::collection($this)->resolve($request);
    }

    /**
     * Find folders where identifier matches the passed
     * array values
     *
     * @param  array  $values
     * @return static
     */
    public function findWhereIdentifierIn($values)
    {
        return $this->filter(function ($folder) use ($values) {
            foreach ($values as $identifier) {
                if ($folder->identifier()->value == $identifier->value) {
                    return true;
                }
            }
        });
    }

    /**
     * Find a database folder by a given folder identifier
     */
    public function findByIdentifier(FolderIdentifier $identifier): ?EmailAccountFolder
    {
        // Used when finding database folder by remote id
        // In this case, the remote identifier is passed
        $key = $identifier->key === 'id' ? 'remote_id' : $identifier->key;

        foreach ($this->items as $folder) {
            if ($folder->{$key} == $identifier->value) {
                return $folder;
            }
        }

        return null;
    }

    /**
     * Sort the folders by their type
     *
     * @return static
     */
    public function sortByType()
    {
        return $this->sortBy(function ($folder) {
            return self::ORDER_MAP[$folder->type] ?? 50;
        })->values();
    }
}
