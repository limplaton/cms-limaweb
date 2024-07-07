<?php
 

namespace Modules\MailClient\Tests\Unit;

use Modules\MailClient\App\Client\FolderIdentifier;
use PHPUnit\Framework\TestCase;

class FolderIdentifierTest extends TestCase
{
    public function test_folder_identifier()
    {
        $identifier = new FolderIdentifier('id', 'INBOX');

        $this->assertSame('id', $identifier->key);
        $this->assertSame('INBOX', $identifier->value);
    }
}
