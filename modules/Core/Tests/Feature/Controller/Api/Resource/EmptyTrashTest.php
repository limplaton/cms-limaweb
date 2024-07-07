<?php
 

namespace Modules\Core\Tests\Feature\Controller\Api\Resource;

use Modules\Contacts\App\Models\Contact;
use Tests\TestCase;

class EmptyTrashTest extends TestCase
{
    public function test_user_can_empty_trash()
    {
        $this->signIn();

        Contact::factory()->count(2)->trashed()->create();

        $this->deleteJson('/api/trashed/contacts?limit=2')->assertJson(['deleted' => 2]);
        $this->assertDatabaseEmpty('contacts');
    }

    public function test_unauthorized_records_are_excluded_from_empty_trash()
    {
        $user = $this->asRegularUser()->signIn();

        Contact::factory()->trashed()->for($user)->create();

        $this->deleteJson('/api/trashed/contacts')->assertJson(['deleted' => 0]);
        $this->assertDatabaseCount('contacts', 1);
    }

    public function test_it_does_not_delete_records_if_bulk_delete_permission_is_not_applied()
    {
        $user = $this->asRegularUser()->withPermissionsTo(['view own contacts', 'delete own contacts'])->signIn();

        Contact::factory()->trashed()->for($user)->create();

        $this->deleteJson('/api/trashed/contacts')->assertJson(['deleted' => 0]);
        $this->assertDatabaseCount('contacts', 1);
    }

    public function test_user_can_empty_trash_in_batches()
    {
        $this->signIn();

        Contact::factory()->count(2)->trashed()->create();

        $this->deleteJson('/api/trashed/contacts?limit=1')->assertJson(['deleted' => 1]);
        $this->assertDatabaseCount('contacts', 1);
        $this->deleteJson('/api/trashed/contacts?limit=1')->assertJson(['deleted' => 1]);
        $this->assertDatabaseEmpty('contacts');
    }
}
