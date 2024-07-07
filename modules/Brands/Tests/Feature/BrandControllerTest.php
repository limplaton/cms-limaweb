<?php
 

namespace Modules\Brands\Tests\Feature;

use Modules\Brands\App\Models\Brand;
use Modules\Core\App\Models\ModelVisibilityGroup;
use Modules\Users\App\Models\User;
use Tests\TestCase;

class BrandControllerTest extends TestCase
{
    protected $localizeableKeys = ['config.signature.bound_text', 'config.document.mail_subject', 'config.document.mail_message', 'config.document.mail_button_text', 'config.document.signed_mail_subject', 'config.document.signed_mail_message', 'config.document.signed_thankyou_message', 'config.document.accepted_thankyou_message'];

    public function test_user_can_create_brand()
    {
        $this->signIn();

        $user = User::factory()->create();

        $otherBrand = Brand::factory()->default()->create();

        $attributes = $this->brandAttributes([
            'visibility_group' => [
                'type' => 'users',
                'depends_on' => [$user->id],
            ],
        ]);

        $this->postJson('/api/brands', $attributes)
            ->assertCreated()
            ->assertJson([
                'name' => 'KONKORD DIGITAL',
                'display_name' => ' CRM',
                'is_default' => true,
                'config' => $attributes['config'],
            ]);

        $brand = Brand::where('id', '!=', $otherBrand->id)->first();

        $this->assertNotNull($brand->visibilityGroup);
        $this->assertSame('users', $brand->visibilityGroup->type);
        $this->assertCount(1, $brand->visibilityGroup->users);
        $this->assertSame($user->id, $brand->visibilityGroup->users[0]->id);
        $this->assertFalse($otherBrand->fresh()->is_default);
    }

    public function test_regular_user_cannot_create_brand()
    {
        $this->asRegularUser()->signIn();

        $this->postJson('/api/brands', $this->brandAttributes())->assertForbidden();
    }

    public function test_user_can_update_brand()
    {
        $this->signIn();

        $user = User::factory()->create();

        $otherBrand = Brand::factory()->default()->create();

        $brand = Brand::factory()->create();

        $attributes = $this->brandAttributes([
            'visibility_group' => [
                'type' => 'users',
                'depends_on' => [$user->id],
            ],
        ]);

        $this->putJson("/api/brands/$brand->id", $attributes)
            ->assertOk()
            ->assertJson([
                'name' => 'KONKORD DIGITAL',
                'display_name' => ' CRM',
                'is_default' => true,
                'config' => $attributes['config'],
            ]);

        $this->assertNotNull($brand->visibilityGroup);
        $this->assertSame('users', $brand->visibilityGroup->type);
        $this->assertCount(1, $brand->visibilityGroup->users);
        $this->assertSame($user->id, $brand->visibilityGroup->users[0]->id);
        $this->assertFalse($otherBrand->fresh()->is_default);
    }

    public function test_it_eager_loads_brand_relationships_when_updating()
    {
        $this->signIn();

        $brand = Brand::factory()->has(
            ModelVisibilityGroup::factory()->users()->hasAttached(User::factory()),
            'visibilityGroup'
        )->create();

        $this->putJson("/api/brands/$brand->id?with=visibilityGroup;visibilityGroup.users", [
            'name' => 'KONKORD DIGITAL',
            'display_name' => ' CRM',
            'is_default' => false,
            'config' => $brand->config,
        ])
            ->assertOk()
            ->assertJsonStructure(['visibility_group' => ['type', 'depends_on']])
            ->assertJsonPath('visibility_group.type', 'users')
            ->assertJsonPath('visibility_group.depends_on', [$brand->visibilityGroup->users->first()->id]);
    }

    public function test_regular_user_cannot_update_brand()
    {
        $this->asRegularUser()->signIn();

        $brand = Brand::factory()->create();

        $this->putJson("/api/brands/$brand->id", $this->brandAttributes())->assertForbidden();
    }

    public function test_user_can_retrieve_brand()
    {
        $this->signIn();

        $brand = Brand::factory()->has(
            ModelVisibilityGroup::factory()->users()->hasAttached(User::factory()),
            'visibilityGroup'
        )->create();

        $this->getJson("/api/brands/$brand->id")
            ->assertOk()
            ->assertJsonStructure(['name', 'display_name', 'is_default', 'config', 'visibility_group']);
    }

    public function test_regular_user_can_retrieve_brand_without_visibility_group()
    {
        $this->asRegularUser()->signIn();
        $brand = Brand::factory()->create();

        $this->getJson("/api/brands/$brand->id")->assertOk();
    }

    public function test_regular_user_can_retrieve_only_visible_brand()
    {
        $this->asRegularUser()->signIn();

        $brand = Brand::factory()->has(
            ModelVisibilityGroup::factory()->users()->hasAttached(User::factory()),
            'visibilityGroup'
        )->create();

        $this->getJson("/api/brands/$brand->id")->assertForbidden();
    }

    public function test_user_can_retrieve_brands()
    {
        $this->signIn();

        Brand::factory()->create();
        Brand::factory()->has(
            ModelVisibilityGroup::factory()->users()->hasAttached(User::factory()),
            'visibilityGroup'
        )->create();

        $this->getJson('/api/brands')
            ->assertOk()
            ->assertJsonCount(2);
    }

    public function test_regular_user_can_retrieve_only_visible_brands()
    {
        $this->asRegularUser()->signIn();

        Brand::factory()->create();

        Brand::factory()->has(
            ModelVisibilityGroup::factory()->users()->hasAttached(User::factory()),
            'visibilityGroup'
        )->create();

        $this->getJson('/api/brands')
            ->assertOk()
            ->assertJsonCount(1);
    }

    public function test_user_can_delete_brand()
    {
        $this->signIn();

        $brands = Brand::factory(2)->create();

        $this->deleteJson("/api/brands/{$brands[0]->id}")->assertNoContent();
        $this->assertDatabaseCount('brands', 1);
    }

    public function test_unauthorized_user_cannot_delete_brand()
    {
        $this->asRegularUser()->signIn();

        $brands = Brand::factory(2)->create();

        $this->deleteJson("/api/brands/{$brands[0]->id}")->assertForbidden();
        $this->assertDatabaseCount('brands', 2);
    }

    public function test_last_brand_cannot_be_deleted()
    {
        $this->signIn();

        $brand = Brand::factory()->create();

        $this->deleteJson("/api/brands/$brand->id")->assertStatus(409);
        $this->assertDatabaseCount('brands', 1);
    }

    public function test_brand_requires_name()
    {
        $this->signIn();

        $brand = Brand::factory()->create();

        $this->postJson('/api/brands', [
            'name' => '',
        ])->assertJsonValidationErrorFor('name');

        $this->putJson("/api/brands/$brand->id", [
            'name' => '',
        ])->assertJsonValidationErrorFor('name');
    }

    public function test_brand_requires_display_name()
    {
        $this->signIn();

        $brand = Brand::factory()->create();

        $this->postJson('/api/brands', [
            'display_name' => '',
        ])->assertJsonValidationErrorFor('display_name');

        $this->putJson("/api/brands/$brand->id", [
            'display_name' => '',
        ])->assertJsonValidationErrorFor('display_name');
    }

    public function test_brand_requires_primary_color()
    {
        $this->signIn();

        $brand = Brand::factory()->create();

        $this->postJson('/api/brands', [
            'config' => ['primary_color' => ''],
        ])->assertJsonValidationErrorFor('config.primary_color');

        $this->putJson("/api/brands/$brand->id", [
            'config' => ['primary_color' => ''],
        ])->assertJsonValidationErrorFor('config.primary_color');
    }

    public function test_localizeable_keys_are_not_required_on_brand_creation()
    {
        $this->signIn();

        $this->postJson('/api/brands', [
            'display_name' => '',
        ])->assertJsonMissingValidationErrors($this->localizeableKeys);
    }

    public function test_it_requires_localizeable_keys_on_brand_update()
    {
        $this->signIn();
        $brand = Brand::factory()->create();
        $data = collect($this->localizeableKeys)->mapWithKeys(fn (string $key) => [$key => []])->undot()->all();

        $this->putJson('/api/brands/'.$brand->id, $data)->assertJsonValidationErrors($this->localizeableKeys);
    }

    public function test_it_requires_localizeable_keys_to_be_array()
    {
        $this->signIn();
        $brand = Brand::factory()->create();
        $data = collect($this->localizeableKeys)->mapWithKeys(fn (string $key) => [$key => 'some'])->undot()->all();

        $this->putJson('/api/brands/'.$brand->id, $data)->assertJsonValidationErrors($this->localizeableKeys);
    }

    protected function brandAttributes($extra = [])
    {
        return array_merge([
            'name' => 'KONKORD DIGITAL',
            'display_name' => ' CRM',
            'is_default' => true,
            'config' => [
                'primary_color' => '#f0f0f0',
                'pdf' => [
                    'font' => 'Arial, sans-serif',
                    'size' => 'a4',
                    'orientation' => 'landscape',
                ],
                'signature' => [
                    'bound_text' => [
                        'en' => 'I Agree',
                    ],
                ],
            ],
        ], $extra);
    }
}
