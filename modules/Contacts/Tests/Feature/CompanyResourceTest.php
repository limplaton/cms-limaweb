<?php
 

namespace Modules\Contacts\Tests\Feature;

use Modules\Activities\App\Models\Activity;
use Modules\Calls\App\Models\Call;
use Modules\Contacts\App\Models\Contact;
use Modules\Contacts\App\Models\Phone;
use Modules\Contacts\App\Models\Source;
use Modules\Core\App\Http\Requests\ResourceRequest;
use Modules\Core\App\Models\Country;
use Modules\Core\Database\Seeders\CountriesSeeder;
use Modules\Core\Tests\ResourceTestCase;
use Modules\Deals\App\Models\Deal;
use Modules\Notes\App\Models\Note;
use Modules\Users\App\Models\Team;
use Modules\Users\App\Models\User;

class CompanyResourceTest extends ResourceTestCase
{
    protected $resourceName = 'companies';

    protected $samplePayload = ['name' => 'KONKORD DIGITAL'];

    public function test_user_can_create_company()
    {
        $this->signIn();
        $this->seed(CountriesSeeder::class);
        $user = $this->createUser();
        $source = Source::factory()->create();
        $contact = Contact::factory()->create();
        $deal = Deal::factory()->create();

        $response = $this->postJson($this->createEndpoint(), [
            'name' => 'KONKORD DIGITAL',
            'domain' => 'crm.com',
            'email' => 'crm@example.com',
            'phones' => [
                ['number' => '+123654-88-885', 'type' => 'work'],
                ['number' => '+123654-77-885', 'type' => 'mobile'],
                ['number' => '+123654-66-885', 'type' => 'other'],
                ['number' => '', 'type' => 'other'],
            ],
            'source_id' => $source->id,
            'user_id' => $user->id,
            'deals' => [$deal->id],
            'contacts' => [$contact->id],
        ])
            ->assertCreated();

        $this->assertResourceJsonStructure($response);

        $response->assertJsonCount(1, 'contacts')
            ->assertJsonCount(1, 'deals')
            ->assertJson([
                'contacts' => [['id' => $contact->id]],
                'deals' => [['id' => $deal->id]],
                'name' => 'KONKORD DIGITAL',
                'domain' => 'crm.com',
                'email' => 'crm@example.com',
                'phones' => [
                    ['number' => '+123654-88-885', 'type' => 'work'],
                    ['number' => '+123654-77-885', 'type' => 'mobile'],
                    ['number' => '+123654-66-885', 'type' => 'other'],
                ],
                'source_id' => $source->id,
                'user_id' => $user->id,
                'was_recently_created' => true,
                'display_name' => 'KONKORD DIGITAL',
                'contacts_count' => 1,
                'deals_count' => 1,
            ]);
    }

    public function test_user_can_update_company()
    {
        $this->seed(CountriesSeeder::class);
        $user = $this->signIn();
        $record = $this->factory()->has(Phone::factory()->count(2), 'phones')
            ->has(Contact::factory())->create();
        $source = Source::factory()->create();
        $contact = Contact::factory()->create();
        $deal = Deal::factory()->create();

        $response = $this->putJson($this->updateEndpoint($record), [
            'name' => 'KONKORD DIGITAL',
            'domain' => 'crm.com',
            'email' => 'crm@example.com',
            'phones' => [
                ['number' => '+136547-96636', 'type' => 'work'],
                ['number' => '+123654-88-885', 'type' => 'work'],
                ['number' => '+123654-77-885', 'type' => 'mobile'],
                ['number' => '+123654-66-885', 'type' => 'other'],
                ['number' => '', 'type' => 'other'],
            ],
            'source_id' => $source->id,
            'source' => ['id' => $source->id],
            'user_id' => $user->id,
            'user' => ['id' => $user->id],
            'deals' => [$deal->id],
            'contacts' => [$contact->id],
        ])
            ->assertOk();

        $this->assertResourceJsonStructure($response);

        $response->assertJsonCount(count($this->resource()->resolveActions(app(ResourceRequest::class))), 'actions')
            ->assertJsonCount(4, 'phones')
            ->assertJsonCount(1, 'contacts')
            ->assertJsonCount(1, 'deals')
            ->assertJson([
                'phones' => [
                    ['number' => '+136547-96636', 'type' => 'work'],
                    ['number' => '+123654-88-885', 'type' => 'work'],
                    ['number' => '+123654-77-885', 'type' => 'mobile'],
                    ['number' => '+123654-66-885', 'type' => 'other'],
                ],
                'contacts' => [['id' => $contact->id]],
                'deals' => [['id' => $deal->id]],
                'name' => 'KONKORD DIGITAL',
                'domain' => 'crm.com',
                'email' => 'crm@example.com',
                'source_id' => $source->id,
                'source' => ['id' => $source->id],
                'user_id' => $user->id,
                'user' => ['id' => $user->id],
                'display_name' => 'KONKORD DIGITAL',
                'contacts_count' => 1,
                'deals_count' => 1,
            ]);
    }

    public function test_it_can_retrieve_companies()
    {
        $this->signIn();

        $this->factory()->count(5)->create();

        $this->getJson($this->indexEndpoint())->assertJsonCount(5, 'data');
    }

    public function test_it_can_retrieve_company()
    {
        $this->signIn();

        $record = $this->factory()->create();

        $this->getJson($this->showEndpoint($record))->assertOk();
    }

    public function test_it_can_globally_search_companies()
    {
        $this->signIn();

        $record = $this->factory()->create();

        $this->getJson("/api/search?q={$record->name}&only=companies")
            ->assertJsonCount(1, '0.data')
            ->assertJsonPath('0.data.0.id', $record->id)
            ->assertJsonPath('0.data.0.path', $record->path())
            ->assertJsonPath('0.data.0.display_name', $record->displayName());
    }

    public function test_an_unauthorized_user_can_global_search_only_companies()
    {

        $user = $this->asRegularUser()->withPermissionsTo('view own companies')->signIn();
        $user1 = $this->createUser();

        $this->factory()->for($user1)->create(['name' => 'KONKORD']);
        $record = $this->factory()->for($user)->create(['name' => 'KONKORD DIGITAL']);

        $this->getJson('/api/search?q=KONKORD&only=companies')
            ->assertJsonCount(1, '0.data')
            ->assertJsonPath('0.data.0.id', $record->id)
            ->assertJsonPath('0.data.0.path', $record->path())
            ->assertJsonPath('0.data.0.display_name', $record->displayName());
    }

    public function test_it_can_search_emails_for_companies()
    {
        $this->signIn();

        $record = $this->factory()->create(['email' => 'konkord@example.com']);

        $this->getJson('/api/search/email-address?q=konkord@example.com')
            ->assertJsonCount(1, '0.data')
            ->assertJsonPath('0.data.0.id', $record->id)
            ->assertJsonPath('0.data.0.path', $record->path())
            ->assertJsonPath('0.data.0.address', 'konkord@example.com')
            ->assertJsonPath('0.data.0.resourceName', $this->resourceName)
            ->assertJsonPath('0.data.0.name', $record->displayName());
    }

    public function test_user_can_export_companies()
    {
        $this->performExportTest();
    }

    public function test_user_can_create_company_with_custom_fields()
    {
        $this->signIn();

        $response = $this->postJson($this->createEndpoint(), array_merge([
            'name' => 'KONKORD DIGITAL',
        ], $this->customFieldsPayload()))->assertCreated();

        $this->assertThatResponseHasCustomFieldsValues($response);
    }

    public function test_user_can_update_company_with_custom_fields()
    {
        $this->signIn();
        $record = $this->factory()->create();

        $response = $this->putJson($this->updateEndpoint($record), array_merge([
            'name' => 'KONKORD DIGITAL',
        ], $this->customFieldsPayload()))->assertOk();

        $this->assertThatResponseHasCustomFieldsValues($response);
    }

    public function test_user_can_import_companies()
    {
        $this->seed(CountriesSeeder::class);
        $this->createUser();

        $this->performImportTest();
    }

    public function test_user_can_import_companies_with_custom_fields()
    {
        $this->seed(CountriesSeeder::class);

        $this->createUser();

        $this->performImportWithCustomFieldsTest();
    }

    public function test_it_properly_finds_duplicate_companies_during_import()
    {
        $this->seed(CountriesSeeder::class);
        $this->createUser();

        $this->factory()->create($overrideValues = [
            'street' => 'Street',
            'city' => 'City',
            'postal_code' => 2400,
            'country_id' => Country::inRandomOrder()->first()->id,
        ]);

        $this->performImportWithDuplicateTest($overrideValues);
    }

    public function test_it_restores_trashed_duplicate_company_during_import()
    {
        $this->seed(CountriesSeeder::class);
        $this->createUser();

        $company = $this->factory()->create(['email' => 'duplicate@example.com']);

        $company->delete();

        $import = $this->performImportUpload($this->createFakeImportFile(
            [$this->createImportHeader(), $this->createImportRow(['email' => 'duplicate@example.com'])]
        ));

        $this->postJson($this->importEndpoint($import), [
            'mappings' => $import->data['mappings'],
        ])->assertOk();

        $this->assertFalse($company->fresh()->trashed());
    }

    public function test_user_properly_finds_duplicate_companies_during_import_via_email()
    {
        $this->seed(CountriesSeeder::class);
        $this->createUser();
        $this->factory()->create(['email' => 'duplicate@example.com', 'street' => null]);

        $this->performImportWithDuplicateTest(['email' => 'duplicate@example.com', 'street' => null]);
    }

    public function test_user_can_load_the_companies_table()
    {
        $this->performTestTableLoad();
    }

    public function test_companies_table_loads_all_fields()
    {
        $this->performTestTableCanLoadWithAllFields();
    }

    public function test_companies_table_can_be_customized()
    {
        $user = $this->signIn();

        $this->postJson($this->tableEndpoint().'/settings', [
            'maxHeight' => '120px',
            'columns' => [
                ['attribute' => 'created_at', 'order' => 2, 'hidden' => false],
                ['attribute' => 'domain', 'order' => 3, 'hidden' => true],
            ],
            'order' => [
                ['attribute' => 'created_at', 'direction' => 'asc'],
                ['attribute' => 'name', 'direction' => 'desc'],
            ],
        ])->assertOk();

        $settings = $this->resource()->resolveTable($this->createRequestForTable($user))->settings();

        $this->assertSame('120px', $settings->maxHeight());
        $this->assertCount(2, $settings->getCustomizedColumns());
        $this->assertCount(2, $settings->getCustomizedOrderBy());
    }

    public function test_it_can_force_delete_company()
    {
        $this->signIn();

        $record = $this->factory()
            ->has(Contact::factory())
            ->has(Note::factory())
            ->has(Call::factory())
            ->has(Activity::factory())
            ->has(Deal::factory())
            ->create();

        $record->delete();

        $this->deleteJson($this->forceDeleteEndpoint($record))->assertNoContent();
        $this->assertDatabaseCount($this->tableName(), 0);
    }

    public function test_it_can_soft_delete_company()
    {
        $this->signIn();

        $record = $this->factory()->create();

        $this->deleteJson($this->deleteEndpoint($record))->assertNoContent();
        $this->assertDatabaseCount($this->tableName(), 1);
    }

    public function test_company_can_be_viewed_without_own_permissions()
    {
        $user = $this->asRegularUser()->signIn();
        $record = $this->factory()->for($user)->create();

        $this->getJson($this->showEndpoint($record))->assertOk()->assertJson(['id' => $record->id]);
    }

    public function test_edit_all_companies_permission()
    {
        $this->asRegularUser()->withPermissionsTo('edit all companies')->signIn();
        $record = $this->factory()->create();

        $this->putJson($this->updateEndpoint($record), $this->samplePayload)->assertOk();
    }

    public function test_edit_own_companies_permission()
    {
        $user = $this->asRegularUser()->withPermissionsTo('edit own companies')->signIn();
        $record1 = $this->factory()->for($user)->create();
        $record2 = $this->factory()->create();

        $this->putJson($this->updateEndpoint($record1), $this->samplePayload)->assertOk();
        $this->putJson($this->updateEndpoint($record2), $this->samplePayload)->assertForbidden();
    }

    public function test_edit_team_companies_permission()
    {
        $user = $this->asRegularUser()->withPermissionsTo('edit team companies')->signIn();
        $teamUser = User::factory()->has(Team::factory()->for($user, 'manager'))->create();

        $record = $this->factory()->for($teamUser)->create();

        $this->putJson($this->updateEndpoint($record))->assertOk();
    }

    public function test_unauthorized_user_cannot_update_company()
    {
        $this->asRegularUser()->signIn();
        $record = $this->factory()->create();

        $this->putJson($this->updateEndpoint($record), $this->samplePayload)->assertForbidden();
    }

    public function test_view_all_companies_permission()
    {
        $this->asRegularUser()->withPermissionsTo('view all companies')->signIn();
        $record = $this->factory()->create();

        $this->getJson($this->showEndpoint($record))->assertOk();
    }

    public function test_view_team_companies_permission()
    {
        $user = $this->asRegularUser()->withPermissionsTo('view team companies')->signIn();
        $teamUser = User::factory()->has(Team::factory()->for($user, 'manager'))->create();

        $record = $this->factory()->for($teamUser)->create();

        $this->getJson($this->showEndpoint($record))->assertOk();
    }

    public function test_user_can_view_own_company()
    {
        $user = $this->asRegularUser()->signIn();
        $record = $this->factory()->for($user)->create();

        $this->getJson($this->showEndpoint($record))->assertOk();
    }

    public function test_unauthorized_user_cannot_view_company()
    {
        $this->asRegularUser()->signIn();
        $record = $this->factory()->create();

        $this->getJson($this->showEndpoint($record))->assertForbidden();
    }

    public function test_delete_any_company_permission()
    {
        $this->asRegularUser()->withPermissionsTo('delete any company')->signIn();

        $record = $this->factory()->create();

        $this->deleteJson($this->deleteEndpoint($record))->assertNoContent();
    }

    public function test_delete_own_companies_permission()
    {
        $user = $this->asRegularUser()->withPermissionsTo('delete own companies')->signIn();

        $record1 = $this->factory()->for($user)->create();
        $record2 = $this->factory()->create();

        $this->deleteJson($this->deleteEndpoint($record1))->assertNoContent();
        $this->deleteJson($this->deleteEndpoint($record2))->assertForbidden();
    }

    public function test_delete_team_companies_permission()
    {
        $user = $this->asRegularUser()->withPermissionsTo('delete team companies')->signIn();
        $teamUser = User::factory()->has(Team::factory()->for($user, 'manager'))->create();

        $record1 = $this->factory()->for($teamUser)->create();
        $record2 = $this->factory()->create();

        $this->deleteJson($this->deleteEndpoint($record1))->assertNoContent();
        $this->deleteJson($this->deleteEndpoint($record2))->assertForbidden();
    }

    public function test_unauthorized_user_cannot_delete_company()
    {
        $this->asRegularUser()->signIn();
        $record = $this->factory()->create();

        $this->deleteJson($this->showEndpoint($record))->assertForbidden();
    }

    public function test_it_empties_companies_trash()
    {
        $this->signIn();

        $this->factory()->count(2)->trashed()->create();

        $this->deleteJson('/api/trashed/companies?limit=2')->assertJson(['deleted' => 2]);
        $this->assertDatabaseEmpty('companies');
    }

    public function test_it_excludes_unauthorized_records_from_empty_companies_trash()
    {
        $user = $this->asRegularUser()->withPermissionsTo(['view own companies', 'delete own companies', 'bulk delete companies'])->signIn();

        $this->factory()->trashed()->create();
        $this->factory()->trashed()->for($user)->create();

        $this->deleteJson('/api/trashed/companies')->assertJson(['deleted' => 1]);
        $this->assertDatabaseCount('companies', 1);
    }

    public function test_it_does_not_empty_companies_trash_if_delete_own_companies_permission_is_not_applied()
    {
        $user = $this->asRegularUser()->withPermissionsTo(['view own companies', 'bulk delete companies'])->signIn();

        $this->factory()->trashed()->for($user)->create();

        $this->deleteJson('/api/trashed/companies')->assertJson(['deleted' => 0]);
        $this->assertDatabaseCount('companies', 1);
    }

    protected function assertResourceJsonStructure($response)
    {
        $response->assertJsonStructure([
            'actions', 'calls_count', 'city', 'contacts', 'contacts_count', 'country', 'country_id', 'created_at', 'deals', 'deals_count', 'display_name', 'domain', 'email', 'id', 'industry', 'industry_id', 'media', 'name', 'next_activity_date', 'notes_count', 'owner_assigned_date', 'parent', 'parent_company_id', 'parents', 'phones', 'postal_code', 'source', 'source_id', 'state', 'street', 'timeline_subject_key', 'incomplete_activities_for_user_count', 'unread_emails_for_user_count', 'updated_at', 'path', 'user', 'user_id', 'was_recently_created', 'tags', 'authorizations' => [
                'create', 'delete', 'update', 'view', 'viewAny',
            ],
        ]);
    }
}
