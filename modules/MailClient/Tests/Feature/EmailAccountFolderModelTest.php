<?php
 

namespace Modules\MailClient\Tests\Feature;

use Illuminate\Support\Facades\Lang;
use Modules\MailClient\App\Client\FolderIdentifier;
use Modules\MailClient\App\Models\EmailAccount;
use Modules\MailClient\App\Models\EmailAccountFolder;
use Modules\MailClient\App\Support\EmailAccountFolderCollection;
use Tests\TestCase;

class EmailAccountFolderModelTest extends TestCase
{
    public function test_folder_has_account()
    {
        $folder = EmailAccountFolder::factory()->for(EmailAccount::factory(), 'account')->create();

        $this->assertInstanceOf(EmailAccount::class, $folder->account);
    }

    public function test_folder_has_identifier()
    {
        $folder = EmailAccountFolder::factory()->create();

        $this->assertInstanceOf(FolderIdentifier::class, $folder->identifier());
    }

    public function test_folder_identifier_uses_name_when_account_is_of_type_imap()
    {
        $folder = EmailAccountFolder::factory()->for(EmailAccount::factory()->imap(), 'account')->create();

        $this->assertEquals($folder->name, $folder->identifier()->value);
        $this->assertEquals('name', $folder->identifier()->key);
    }

    public function test_folder_identifier_uses_id_when_account_is_not_of_type_imap()
    {
        $folder = EmailAccountFolder::factory()->for(EmailAccount::factory()->gmail(), 'account')->create();

        $this->assertEquals($folder->remote_id, $folder->identifier()->value);
        $this->assertEquals('id', $folder->identifier()->key);

        $folder = EmailAccountFolder::factory()->for(EmailAccount::factory()->outlook(), 'account')->create();

        $this->assertEquals($folder->remote_id, $folder->identifier()->value);
        $this->assertEquals('id', $folder->identifier()->key);
    }

    public function test_folder_has_display_name()
    {
        $folder = EmailAccountFolder::factory()->create(['display_name' => 'TEST DISPLAY NAME']);

        $this->assertSame('TEST DISPLAY NAME', $folder->display_name);
    }

    public function test_folder_display_name_can_be_custom_translated()
    {
        Lang::addLines([
            'custom.mail.labels.INBOX-TEST' => 'Custom folder name',
        ], 'en');

        $folder = EmailAccountFolder::factory()->create(['display_name' => 'INBOX-TEST']);

        $this->assertEquals('Custom folder name', $folder->display_name);
    }

    public function test_when_exists_it_uses_the_predefined_display_name_from_language_file()
    {
        Lang::addLines([
            'custom.mail.labels.INBOX-TEST' => 'Custom folder name',
        ], 'en');

        $folder = EmailAccountFolder::factory()->create(['display_name' => 'INBOX-TEST']);

        $this->assertEquals('Custom folder name', $folder->display_name);
    }

    public function test_it_does_not_use_predefined_display_name_if_custom_display_name_is_added()
    {
        Lang::addLines([
            'mailclient::mail.labels.INBOX-TEST' => 'Custom folder name',
            'custom.mail.labels.INBOX-TEST' => 'Custom prioritized name',
        ], 'en');

        $folder = EmailAccountFolder::factory()->create(['display_name' => 'INBOX-TEST']);

        $this->assertEquals('Custom prioritized name', $folder->display_name);
    }

    public function test_folder_uses_custom_collection()
    {
        $folder = new EmailAccountFolder;

        $this->assertInstanceOf(EmailAccountFolderCollection::class, $folder->newCollection());
    }
}
