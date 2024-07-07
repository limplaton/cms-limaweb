<?php
 

namespace Modules\MailClient\Tests\Unit;

use Modules\MailClient\App\Client\AbstractFolder;
use Modules\MailClient\App\Client\FolderCollection;
use PHPUnit\Framework\TestCase;

class FolderCollectionTest extends TestCase
{
    public function test_it_can_find_folder_by_identifier()
    {
        $folders = new FolderCollection([
            new Folder([], 'INBOX'),
            (new Folder([], 'SENT'))->setChildren([new Folder([], 'SENT.Custom Sent')]),
        ]);
        $inboxFolder = $folders->get(0);
        $sentFolder = $folders->get(1);

        $this->assertEquals('INBOX', $folders->find($inboxFolder->identifier())->getName());
        $this->assertEquals('SENT', $folders->find($sentFolder->identifier())->getName());
        $this->assertEquals('SENT.Custom Sent', $folders->find($sentFolder->getChildren()[0]->identifier())->getName());
    }

    public function test_it_can_create_tree_from_folders()
    {
        $folders = new FolderCollection([
            new Folder([], 'INBOX'),
            new Folder([], 'SENT'),
            new Folder([], 'SENT.Custom Sent'),
        ]);

        $tree = $folders->createTreeFromDelimiter('.');

        $this->assertCount(2, $tree);
        $this->assertEquals('INBOX', $tree[0]->getName());
        $this->assertEquals('SENT', $tree[1]->getName());
        $this->assertCount(1, $tree[1]->getChildren());
        $this->assertEquals('SENT.Custom Sent', $tree[1]->getChildren()[0]->getName());
    }

    public function test_it_can_flatten_all_folders()
    {
        $folders = new FolderCollection([
            new Folder([], 'INBOX'),
            (new Folder([], 'SENT'))->setChildren([
                (new Folder([], 'SENT.Child 1'))->setChildren([new Folder([], 'Sent.Child 2')]),
            ]),
        ]);

        $this->assertCount(4, $folders->flatten());
    }
}

class Folder extends AbstractFolder
{
    protected $name;

    public function __construct($entity, $name = 'INBOX')
    {
        $this->name = $name;
        parent::__construct($entity);
    }

    public function getId()
    {
        return $this->name;
    }

    public function getMessages()
    {
    }

    public function getMessagesFrom($dateTime)
    {
    }

    public function getMessage($uid)
    {
    }

    public function getName()
    {
        return $this->name;
    }

    public function getDisplayName()
    {
        return $this->name;
    }

    public function isSelectable()
    {
        return true;
    }
}
