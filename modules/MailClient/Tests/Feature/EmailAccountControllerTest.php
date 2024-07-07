<?php
 

namespace Modules\MailClient\Tests\Feature;

use Modules\MailClient\App\Client\ConnectionType;
use Modules\MailClient\App\Models\EmailAccount;
use Modules\MailClient\App\Models\EmailAccountFolder;
use Tests\TestCase;

class EmailAccountControllerTest extends TestCase
{
    public function test_user_can_create_imap_account()
    {
        $this->signIn();
        $payload = EmailAccount::factory()->shared()->imap()->make()->toArray();
        $payload['password'] = 'password';
        $payload['create_contact'] = true;
        $payload['from_name_header'] = EmailAccount::DEFAULT_FROM_NAME_HEADER;

        $inboxFolder = EmailAccountFolder::factory()->inbox()->raw([
            'email_account_id' => null,
        ]);

        $sentFolder = EmailAccountFolder::factory()->sent()->raw([
            'email_account_id' => null,
            'syncable' => false,
        ]);

        $trashFolder = EmailAccountFolder::factory()->trash()->raw([
            'email_account_id' => null,
            'syncable' => false,
        ]);

        $payload['folders'] = [$inboxFolder, $sentFolder, $trashFolder];

        $this->postJson('/api/mail/accounts', $payload)
            ->assertCreated()
            ->assertJson([
                'is_initial_sync_performed' => false,
                'sync_state_comment' => null,
                'email' => $payload['email'],
                'connection_type' => $payload['connection_type'],
                'requires_auth' => false,
                'is_sync_disabled' => false,
                'is_sync_stopped' => false,
                'type' => 'shared',
                'is_shared' => true,
                'is_personal' => false,
                'create_contact' => true,
                'imap_server' => 'imap.example.com',
                'imap_port' => 993,
                'imap_encryption' => 'ssl',
                'smtp_server' => 'smtp.example.com',
                'smtp_port' => 465,
                'smtp_encryption' => 'ssl',
                'validate_cert' => false,
                'is_primary' => false,
                'was_recently_created' => true,
                'from_name_header' => '{agent} from {company}',
                'sent_folder' => ['name' => $sentFolder['name']],
                'trash_folder' => ['name' => $trashFolder['name']],
            ])->assertJsonCount(3, 'folders')
            ->assertJsonCount(3, 'folders_tree')
            ->assertJsonCount(1, 'active_folders')
            ->assertJsonCount(1, 'active_folders_tree')
            ->assertJsonStructure([
                'id', 'sent_folder', 'trash_folder', 'created_at',
                'updated_at', 'authorizations', 'sent_folder_id', 'trash_folder_id', 'formatted_from_name_header',
            ]);
    }

    public function test_email_account_requires_valid_connection_type()
    {
        $this->signIn();

        $this->postJson('/api/mail/accounts', [
            'connection_type' => 'invalid',
        ])->assertJsonValidationErrorFor('connection_type');
    }

    public function test_connection_type_is_required_only_when_creating()
    {
        $this->signIn();
        $account = EmailAccount::factory()->create();

        $this->putJson('/api/mail/accounts/'.$account->id, [
            'smtp_port' => 993,
        ])->assertJsonMissingValidationErrors('connection_type');
    }

    public function test_email_is_required_only_when_creating()
    {
        $this->signIn();

        $this->postJson('/api/mail/accounts', [
            'email' => '',
            'connection_type' => ConnectionType::Imap,
        ])->assertJsonValidationErrorFor('email');

        $account = EmailAccount::factory()->create();

        $this->putJson('/api/mail/accounts/'.$account->id, [
            'connection_type' => ConnectionType::Imap,
        ])->assertJsonMissingValidationErrors('email');
    }

    public function test_email_must_be_unique()
    {
        $this->signIn();

        $account = EmailAccount::factory()->create();

        $this->postJson('/api/mail/accounts', [
            'email' => $account->email,
        ])->assertJsonValidationErrorFor('email');
    }

    public function test_account_requires_valid_email()
    {
        $this->signIn();

        $this->postJson('/api/mail/accounts', [
            'email' => 'invalid',
        ])->assertJsonValidationErrorFor('email');
    }

    public function test_account_requires_password_on_creation()
    {
        $this->signIn();

        $this->postJson('/api/mail/accounts', [
            'password' => '',
        ])->assertJsonValidationErrorFor('password');
    }

    public function test_account_does_not_requires_password_on_update()
    {
        $this->signIn();

        $account = EmailAccount::factory()->create();

        $this->putJson('/api/mail/accounts/'.$account->id, [
            'password' => '',
        ])->assertJsonMissingValidationErrors('password');
    }

    public function test_account_requires_sent_folder_only_when_updating()
    {
        $this->signIn();

        $this->postJson('/api/mail/accounts', [
            'sent_folder_id' => null,
        ])->assertJsonMissingValidationErrors('sent_folder_id');

        $account = EmailAccount::factory()->create();

        $this->putJson('/api/mail/accounts/'.$account->id, [
            'sent_folder_id' => null,
        ])->assertJsonValidationErrorFor('sent_folder_id');
    }

    public function test_account_requires_trash_folder_only_when_updating()
    {
        $this->signIn();

        $this->postJson('/api/mail/accounts', [
            'trash_folder_id' => null,
        ])->assertJsonMissingValidationErrors('trash_folder_id');

        $account = EmailAccount::factory()->create();

        $this->putJson('/api/mail/accounts/'.$account->id, [
            'trash_folder_id' => null,
        ])->assertJsonValidationErrorFor('trash_folder_id');
    }

    public function test_when_creating_from_name_header_is_required_for_shared_accounts()
    {
        $this->signIn();

        $this->postJson('/api/mail/accounts', [
            'from_name_header' => '',
            'type' => 'shared',
        ])->assertJsonValidationErrorFor('from_name_header');
    }

    public function test_when_creating_from_name_header_is_not_required_for_personal_accounts()
    {
        $user = $this->signIn();

        $this->postJson('/api/mail/accounts', [
            'user_id' => $user->id,
            'from_name_header' => '',
            'type' => 'personal',
        ])->assertJsonMissingValidationErrors('from_name_header');
    }

    public function test_initial_sync_cant_be_older_then_6_months()
    {
        $this->signIn();

        $this->postJson('/api/mail/accounts', [
            'initial_sync_from' => now()->subMonths(7),
        ])->assertJsonValidationErrors([
            'initial_sync_from' => 'The initial synchronization date must not be older then 6 months.',
        ]);
    }
}
