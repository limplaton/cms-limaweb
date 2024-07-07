<?php
 

namespace Modules\MailClient\App\Client;

use Illuminate\Support\Collection;

class FolderCollection extends Collection
{
    /**
     * Find a folder by a given identifier
     *
     * @return null|\Modules\MailClient\App\Client\Contracts\FolderInterface
     */
    public function find(FolderIdentifier $identifier)
    {
        return $this->findDeep($identifier);
    }

    /**
     * Deep find a folder by a given identifier
     *
     * @param  \Illuminate\Support\Collection|array  $folders
     * @return null|\Modules\MailClient\App\Client\Contracts\FolderInterface
     */
    public function findDeep(FolderIdentifier $identifier, $folders = null)
    {
        $retval = null;

        $folders ??= $this->items;

        foreach ($folders as $folder) {
            $retval = $folder->{$identifier->key} == $identifier->value ?
            $folder :
            $this->findDeep($identifier, $folder->getChildren());

            if ($retval) {
                break;
            }
        }

        return $retval;
    }

    /**
     * Create tree from delimiter
     *
     * @param  null|string  $delimiter
     * @return static
     */
    public function createTreeFromDelimiter($delimiter = null)
    {
        $tree = $this->explodeTree($delimiter);

        return $this->newCollectionFromTree($tree);
    }

    /**
     * Get all folders including the child in one array
     *
     * @param  mixed  $depth  [NOT APPLICABLE]
     * @return static
     */
    public function flatten($depth = INF)
    {
        $data = [];

        foreach ($this->items as $folder) {
            $data[] = $folder;
            $data = array_merge($data, $this->extractChildren($folder));
        }

        return new self($data);
    }

    /**
     * Deep extract all folder children
     *
     * @param  \Modules\MailClient\App\Client\Contracts\FolderInterface  $folder
     * @return array
     */
    protected function extractChildren($folder)
    {
        $folders = [];

        foreach ($folder->getChildren() as $child) {
            $folders[] = $child;

            if (count($child->getChildren()) > 0) {
                $folders = array_merge($folders, $this->extractChildren($child));
            }
        }

        return $folders;
    }

    /**
     * Create tree from folders delimiter
     *
     * @param  null|string  $delimiter
     * @param  bool  $includeBaseValue
     * @return array
     */
    protected function explodeTree($delimiter)
    {
        $treeResult = [];

        foreach ($this->items as $folder) {
            $folderDelimiter = $this->determineTreeDelimiter($folder, $delimiter);

            if (! empty($folderDelimiter)) {
                $parts = explode($folderDelimiter, $folder->getName());
            } else {
                $parts = [$folder->getName()];
            }

            $leafPart = array_pop($parts);

            // Build parent structure
            // Might be slow for really deep and large structures
            $parentArr = &$treeResult;
            foreach ($parts as $part) {
                if (! isset($parentArr[$part])) {
                    $parentArr[$part] = [];
                } elseif (! is_array($parentArr[$part])) {
                    $parentArr[$part] = ['__base_val' => $parentArr[$part]];
                }
                $parentArr = &$parentArr[$part];
            }

            // Add the final part to the structure
            if (empty($parentArr[$leafPart])) {
                $parentArr[$leafPart] = $folder;
            } elseif (is_array($parentArr[$leafPart])) {
                $parentArr[$leafPart]['__base_val'] = $folder;
            }
        }

        return $treeResult;
    }

    /**
     * Transform the created tree from delimiter to appropriate format
     *
     * @param  array  $tree
     * @return static
     */
    protected function newCollectionFromTree($tree)
    {
        $items = [];

        foreach ($tree as $key => $folders) {
            $hasChildren = is_array($folders);

            if ($hasChildren) {
                // This may happen when the folder name already contains the delimiter in the name
                // for example, Gmail allows the user to enter the delimiter in the folder name e.q. [Gmail]/Trash/_Archive
                // In this case, this is one folder and it's not supported as we cannot create a tree from one folder nor
                // determine whether this folder has child folders
                if (! isset($folders['__base_val'])) {
                    continue;
                }

                $parent = $folders['__base_val'];
                unset($folders['__base_val']);
            }

            if (! $hasChildren) {
                $items[] = $folders;
            } else {
                $parent->setChildren($this->newCollectionFromTree($folders));
                $items[] = $parent;
            }
        }

        return new static($items);
    }

    /**
     * Determine the tree delimiter
     *
     * @param  \Modules\MailClient\App\Client\Contracts\FolderInterface  $folder
     * @param  string  $default
     * @return string
     */
    protected function determineTreeDelimiter($folder, $default)
    {
        if (! method_exists($folder, 'getDelimiter')) {
            return $default;
        }

        return $folder->getDelimiter();
    }
}
