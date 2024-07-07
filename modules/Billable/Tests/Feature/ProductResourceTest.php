<?php
 

namespace Modules\Billable\Tests\Feature;

use Modules\Core\Tests\ResourceTestCase;
use Modules\Users\App\Models\Team;
use Modules\Users\App\Models\User;

class ProductResourceTest extends ResourceTestCase
{
    protected $resourceName = 'products';

    protected $samplePayload = ['name' => 'Macbook Air', 'unit_price' => 1500];

    public function test_user_can_create_resource_record()
    {
        $this->signIn();

        $response = $this->postJson($this->createEndpoint(), $payload = [
            'name' => 'Macbook Pro',
            'description' => 'INTEL',
            'direct_cost' => 1250,
            'unit_price' => 1500,
            'is_active' => true,
            'sku' => 'MP-2018',
            'tax_label' => 'DDV',
            'tax_rate' => 18,
            'unit' => 'kg',
        ])
            ->assertCreated();

        $this->assertResourceJsonStructure($response);

        $response->assertJson($payload)
            ->assertJson([
                'was_recently_created' => true,
                'display_name' => 'Macbook Pro',
            ]);
    }

    public function test_user_can_update_resource_record()
    {
        $this->signIn();
        $record = $this->factory()->create();

        $response = $this->putJson($this->updateEndpoint($record), $payload = [
            'name' => 'Macbook Air',
            'description' => 'INTEL',
            'direct_cost' => 1250,
            'unit_price' => 1500,
            'is_active' => false,
            'sku' => 'MP-2018',
            'tax_label' => 'DDV',
            'tax_rate' => 18,
            'unit' => 'kg',
        ])
            ->assertOk();

        $this->assertResourceJsonStructure($response);

        $response->assertJson($payload)
            ->assertJson([
                'display_name' => 'Macbook Air',
            ]);
    }

    public function test_user_can_retrieve_resource_records()
    {
        $this->signIn();

        $this->factory()->count(5)->create();

        $this->getJson($this->indexEndpoint())->assertJsonCount(5, 'data');
    }

    public function test_user_can_retrieve_resource_record()
    {
        $this->signIn();

        $record = $this->factory()->create();

        $this->getJson($this->showEndpoint($record))->assertOk();
    }

    public function test_user_can_globally_search_products()
    {
        $this->signIn();

        $record = $this->factory()->create();

        $this->getJson("/api/search?q={$record->name}&only=products")
            ->assertJsonCount(1, '0.data')
            ->assertJsonPath('0.data.0.id', $record->id)
            ->assertJsonPath('0.data.0.path', $record->path())
            ->assertJsonPath('0.data.0.display_name', $record->displayName());
    }

    public function test_an_unauthorized_user_can_global_search_only_own_records()
    {
        $user = $this->asRegularUser()->withPermissionsTo('view own products')->signIn();
        $user1 = $this->createUser();

        $this->factory()->for($user1, 'creator')->create(['name' => 'PRODUCT KONKORD']);
        $record = $this->factory()->for($user, 'creator')->create(['name' => 'PRODUCT INOKLAPS']);

        $this->getJson('/api/search?q=PRODUCT&only=products')
            ->assertJsonCount(1, '0.data')
            ->assertJsonPath('0.data.0.id', $record->id)
            ->assertJsonPath('0.data.0.path', $record->path())
            ->assertJsonPath('0.data.0.display_name', $record->displayName());
    }

    public function test_user_can_export_products()
    {
        $this->performExportTest();
    }

    public function test_user_can_create_resource_record_with_custom_fields()
    {
        $this->signIn();

        $response = $this->postJson($this->createEndpoint(), array_merge([
            'name' => 'Macbook Pro',
            'unit_price' => 1500,
            'tax_label' => 'DDV',
            'tax_rate' => 18,
        ], $this->customFieldsPayload()))->assertCreated();

        $this->assertThatResponseHasCustomFieldsValues($response);
    }

    public function test_user_can_update_resource_record_with_custom_fields()
    {
        $this->signIn();
        $record = $this->factory()->create();

        $response = $this->putJson($this->updateEndpoint($record), array_merge([
            'name' => 'Macbook Pro',
            'unit_price' => 1500,
            'tax_label' => 'DDV',
            'tax_rate' => 18,
        ], $this->customFieldsPayload()))->assertOk();

        $this->assertThatResponseHasCustomFieldsValues($response);
    }

    public function test_user_can_import_products()
    {
        $this->createUser();

        $this->performImportTest();
    }

    public function test_user_can_import_products_with_custom_fields()
    {
        $this->createUser();

        $this->performImportWithCustomFieldsTest();
    }

    public function test_it_finds_duplicate_products_during_import_via_name()
    {
        $this->createUser();
        $this->factory()->create(['name' => 'Duplicate Name']);

        $this->performImportWithDuplicateTest(['name' => 'Duplicate Name']);
    }

    public function test_it_finds_duplicate_products_during_import_via_sku()
    {
        $this->createUser();
        $this->factory()->create(['sku' => '001']);

        $this->performImportWithDuplicateTest(['sku' => '001']);
    }

    public function test_it_restores_trashed_duplicate_product_during_import()
    {
        $this->createUser();

        $product = $this->factory()->create(['sku' => '001']);

        $product->delete();

        $import = $this->performImportUpload($this->createFakeImportFile(
            [$this->createImportHeader(), $this->createImportRow(['sku' => '001'])]
        ));

        $this->postJson($this->importEndpoint($import), [
            'mappings' => $import->data['mappings'],
        ])->assertOk();

        $this->assertFalse($product->fresh()->trashed());
    }

    public function test_user_can_load_the_products_table()
    {
        $this->performTestTableLoad();
    }

    public function test_products_table_loads_all_fields()
    {
        $this->performTestTableCanLoadWithAllFields();
    }

    public function test_products_table_can_be_customized()
    {
        $user = $this->signIn();

        $this->postJson($this->tableEndpoint().'/settings', [
            'maxHeight' => '120px',
            'columns' => [
                ['attribute' => 'created_at', 'order' => 2, 'hidden' => false],
                ['attribute' => 'name', 'order' => 3, 'hidden' => false],
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

    public function test_user_can_force_delete_resource_record()
    {
        $this->signIn();

        $record = tap($this->factory()->create())->delete();

        $this->deleteJson($this->forceDeleteEndpoint($record))->assertNoContent();
        $this->assertDatabaseCount($this->tableName(), 0);
    }

    public function test_user_can_soft_delete_resource_record()
    {
        $this->signIn();

        $record = $this->factory()->create();

        $this->deleteJson($this->deleteEndpoint($record))->assertNoContent();
        $this->assertDatabaseCount($this->tableName(), 1);
    }

    public function test_product_can_be_viewed_without_own_permissions()
    {
        $user = $this->asRegularUser()->signIn();
        $record = $this->factory()->for($user, 'creator')->create();

        $this->getJson($this->showEndpoint($record))->assertOk()->assertJson(['id' => $record->id]);
    }

    public function test_edit_all_products_permission()
    {
        $this->asRegularUser()->withPermissionsTo('edit all products')->signIn();
        $record = $this->factory()->create();

        $this->putJson($this->updateEndpoint($record), $this->samplePayload)->assertOk();
    }

    public function test_edit_own_products_permission()
    {
        $user = $this->asRegularUser()->withPermissionsTo('edit own products')->signIn();
        $record1 = $this->factory()->for($user, 'creator')->create();
        $record2 = $this->factory()->create();

        $this->putJson($this->updateEndpoint($record1), $this->samplePayload)->assertOk();
        $this->putJson($this->updateEndpoint($record2), $this->samplePayload)->assertForbidden();
    }

    public function test_edit_team_products_permission()
    {
        $user = $this->asRegularUser()->withPermissionsTo('edit team products')->signIn();
        $teamUser = User::factory()->has(Team::factory()->for($user, 'manager'))->create();

        $record = $this->factory()->for($teamUser, 'creator')->create();

        $this->putJson($this->updateEndpoint($record))->assertOk();
    }

    public function test_unauthorized_user_cannot_update_product()
    {
        $this->asRegularUser()->signIn();
        $record = $this->factory()->create();

        $this->putJson($this->updateEndpoint($record), $this->samplePayload)->assertForbidden();
    }

    public function test_view_all_products_permission()
    {
        $this->asRegularUser()->withPermissionsTo('view all products')->signIn();
        $record = $this->factory()->create();

        $this->getJson($this->showEndpoint($record))->assertOk();
    }

    public function test_view_team_products_permission()
    {
        $user = $this->asRegularUser()->withPermissionsTo('view team products')->signIn();
        $teamUser = User::factory()->has(Team::factory()->for($user, 'manager'))->create();

        $record = $this->factory()->for($teamUser, 'creator')->create();

        $this->getJson($this->showEndpoint($record))->assertOk();
    }

    public function test_user_can_view_own_product()
    {
        $user = $this->asRegularUser()->signIn();
        $record = $this->factory()->for($user, 'creator')->create();

        $this->getJson($this->showEndpoint($record))->assertOk();
    }

    public function test_unauthorized_user_cannot_view_product()
    {
        $this->asRegularUser()->signIn();
        $record = $this->factory()->create();

        $this->getJson($this->showEndpoint($record))->assertForbidden();
    }

    public function test_delete_any_product_permission()
    {
        $this->asRegularUser()->withPermissionsTo('delete any product')->signIn();

        $record = $this->factory()->create();

        $this->deleteJson($this->deleteEndpoint($record))->assertNoContent();
    }

    public function test_delete_own_products_permission()
    {
        $user = $this->asRegularUser()->withPermissionsTo('delete own products')->signIn();

        $record1 = $this->factory()->for($user, 'creator')->create();
        $record2 = $this->factory()->create();

        $this->deleteJson($this->deleteEndpoint($record1))->assertNoContent();
        $this->deleteJson($this->deleteEndpoint($record2))->assertForbidden();
    }

    public function test_delete_team_products_permission()
    {
        $user = $this->asRegularUser()->withPermissionsTo('delete team products')->signIn();
        $teamUser = User::factory()->has(Team::factory()->for($user, 'manager'))->create();

        $record1 = $this->factory()->for($teamUser, 'creator')->create();
        $record2 = $this->factory()->create();

        $this->deleteJson($this->deleteEndpoint($record1))->assertNoContent();
        $this->deleteJson($this->deleteEndpoint($record2))->assertForbidden();
    }

    public function test_unauthorized_user_cannot_delete_product()
    {
        $this->asRegularUser()->signIn();
        $record = $this->factory()->create();

        $this->deleteJson($this->showEndpoint($record))->assertForbidden();
    }

    public function test_it_empties_products_trash()
    {
        $this->signIn();

        $this->factory()->count(2)->trashed()->create();

        $this->deleteJson('/api/trashed/products?limit=2')->assertJson(['deleted' => 2]);
        $this->assertDatabaseEmpty('products');
    }

    public function test_it_excludes_unauthorized_records_from_empty_products_trash()
    {
        $user = $this->asRegularUser()->withPermissionsTo(['view own products', 'delete own products', 'bulk delete products'])->signIn();

        $this->factory()->trashed()->create();
        $this->factory()->trashed()->for($user, 'creator')->create();

        $this->deleteJson('/api/trashed/products')->assertJson(['deleted' => 1]);
        $this->assertDatabaseCount('products', 1);
    }

    public function test_it_does_not_empty_products_trash_if_delete_own_products_permission_is_not_applied()
    {
        $user = $this->asRegularUser()->withPermissionsTo(['view own products', 'bulk delete products'])->signIn();

        $this->factory()->trashed()->for($user, 'creator')->create();

        $this->deleteJson('/api/trashed/products')->assertJson(['deleted' => 0]);
        $this->assertDatabaseCount('products', 1);
    }

    protected function assertResourceJsonStructure($response)
    {
        $response->assertJsonStructure([
            'actions', 'created_at', 'created_by', 'description', 'direct_cost', 'display_name', 'id', 'is_active', 'name', 'sku', 'tax_label', 'tax_rate', 'unit', 'unit_price', 'updated_at', 'path', 'was_recently_created', 'authorizations' => [
                'create', 'delete', 'update', 'view', 'viewAny',
            ],
        ]);
    }
}
