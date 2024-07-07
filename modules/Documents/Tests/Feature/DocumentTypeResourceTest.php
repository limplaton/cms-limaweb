<?php
 

namespace Modules\Documents\Tests\Feature;

use Modules\Core\App\Models\ModelVisibilityGroup;
use Modules\Documents\App\Models\Document;
use Modules\Documents\App\Models\DocumentType;
use Modules\Users\App\Models\Team;
use Modules\Users\App\Models\User;
use Tests\TestCase;

class DocumentTypeResourceTest extends TestCase
{
    public function test_user_can_create_document_type()
    {
        $this->signIn();

        $this->postJson('/api/document-types', [
            'name' => 'Type Name',
            'swatch_color' => '#ffffff',
        ])->assertCreated()->assertJson([
            'name' => 'Type Name',
            'swatch_color' => '#ffffff',
        ]);
    }

    public function test_document_type_requires_name()
    {
        $this->signIn();

        $this->postJson('/api/document-types', [
            'name' => '',
        ])->assertJsonValidationErrorFor('name');

        $type = DocumentType::factory()->create();

        $this->putJson("/api/document-types/$type->id", [
            'name' => '',
        ])->assertJsonValidationErrorFor('name');
    }

    public function test_document_template_color_cannot_exceed_more_than_7_characters()
    {
        $this->signIn();

        $this->postJson('/api/document-types', [
            'swatch_color' => '#f0f0f0f',
        ])->assertJsonValidationErrorFor('swatch_color');

        $type = DocumentType::factory()->create();

        $this->putJson("/api/document-types/$type->id", [
            'swatch_color' => '#f0f0f0f',
        ])->assertJsonValidationErrorFor('swatch_color');
    }

    public function test_user_can_update_document_type()
    {
        $this->signIn();

        $type = DocumentType::factory()->create();

        $this->putJson("/api/document-types/$type->id", [
            'name' => 'Updated Type Name',
            'swatch_color' => '#ffffff',
        ])->assertOk()->assertJson([
            'name' => 'Updated Type Name',
            'swatch_color' => '#ffffff',
        ]);
    }

    public function test_regular_user_cant_update_document_type()
    {
        $this->asRegularUser()->signIn();

        $type = DocumentType::factory()->create();

        $this->putJson("/api/document-types/$type->id", [
            'name' => 'Updated Type Name',
        ])->assertForbidden();
    }

    public function test_document_types_can_be_retrieved()
    {
        $this->signIn();

        DocumentType::factory(2)->create();

        $this->getJson('/api/document-types')->assertOk()->assertJsonCount(2, 'data');
    }

    public function test_document_type_can_be_deleted()
    {
        $this->signIn();

        $type = DocumentType::factory()->create();

        $this->deleteJson("/api/document-types/$type->id")->assertNoContent();

        $this->assertDatabaseMissing('document_types', ['id' => $type->id]);
    }

    public function test_regular_user_cant_delete_document_type()
    {
        $this->asRegularUser()->signIn();

        $type = DocumentType::factory()->create();

        $this->deleteJson("/api/document-types/$type->id")->assertForbidden();

        $this->assertDatabaseHas('document_types', ['id' => $type->id]);
    }

    public function test_document_template_can_be_retrieved()
    {
        $this->asRegularUser()->signIn();

        $type = DocumentType::factory()->create();

        $this->getJson("/api/document-types/$type->id")
            ->assertOk()
            ->assertJsonStructure(['name', 'swatch_color', 'icon'])
            ->assertJson([
                'name' => $type->name,
                'swatch_color' => $type->swatch_color,
            ]);
    }

    public function test_visibility_group_can_be_set_on_create()
    {
        $this->signIn();

        $user = $this->createUser();

        $this->postJson(
            '/api/document-types',
            $payload = [
                'name' => 'Type Name',
                'visibility_group' => [
                    'type' => 'users',
                    'depends_on' => [$user->id],
                ],
            ]
        )
            ->assertCreated()
            ->assertJson($payload);
    }

    public function test_on_update_it_does_not_delete_visibility_group_if_not_provided()
    {
        $this->signIn();

        $type = $this->newTypeFactoryWithVisibilityGroup('users', User::factory())->create();

        $this->putJson(
            "/api/document-types/$type->id",
            ['name' => 'Updated Name']
        )
            ->assertOk()
            ->assertJsonStructure(['visibility_group'])
            ->assertJsonPath('visibility_group.type', 'users')
            ->assertJsonPath('visibility_group.depends_on', [$type->visibilityGroup->users[0]->id]);
    }

    public function test_admin_user_can_see_retrieve_all_visibility_restricted_document_types()
    {
        $this->signIn();

        DocumentType::factory()->create();

        $this->newTypeFactoryWithVisibilityGroup('users', User::factory())->create();

        $this->getJson('/api/document-types')->assertOk()->assertJsonCount(2, 'data');
    }

    public function test_admin_user_can_see_retrieve_visibility_restricted_document_type()
    {
        $this->signIn();

        $type = $this->newTypeFactoryWithVisibilityGroup('users', User::factory())->create();

        $this->getJson("/api/document-types/$type->id")->assertOk();
    }

    public function test_admin_user_can_delete_visibility_restricted_document_type()
    {
        $this->signIn();

        $type = $this->newTypeFactoryWithVisibilityGroup('users', User::factory())->create();

        $this->deleteJson("/api/document-types/$type->id")->assertNoContent();
        $this->assertDatabaseMissing('document_types', ['id' => $type->id]);
    }

    public function test_it_applies_document_types_visibility_restriction_for_users()
    {
        $this->asRegularUser()->signIn();

        $type = $this->newTypeFactoryWithVisibilityGroup('users', User::factory())->create();

        $this->getJson('/api/document-types')->assertOk()->assertJsonCount(0, 'data');

        $this->getJson("/api/document-types/$type->id")->assertForbidden();
    }

    public function test_it_applies_document_types_visibility_restriction_for_teams()
    {
        $user = $this->asRegularUser()->signIn();

        $otherTeam = Team::factory()->create();
        $user->teams()->attach($otherTeam->id);

        $type = $this->newTypeFactoryWithVisibilityGroup('teams', Team::factory())->create();

        $this->getJson('/api/document-types')->assertOk()->assertJsonCount(0, 'data');

        $this->getJson("/api/document-types/$type->id")->assertForbidden();
    }

    public function test_user_can_see_authorized_document_types_based_on_teams_visibility_group()
    {
        $user = $this->asRegularUser()->signIn();

        $team = Team::factory()->create();
        $user->teams()->attach($team->id);

        $type = $this->newTypeFactoryWithVisibilityGroup('teams', $team)->create();

        $this->getJson('/api/document-types')->assertOk()->assertJsonCount(1, 'data');

        $this->getJson("/api/document-types/$type->id")->assertOk();
    }

    public function test_user_can_see_authorized_document_types_based_on_users_visibility_group()
    {
        $user = $this->asRegularUser()->signIn();

        $type = $this->newTypeFactoryWithVisibilityGroup('users', $user)->create();

        $this->getJson('/api/document-types')->assertOk()->assertJsonCount(1, 'data');

        $this->getJson("/api/document-types/$type->id")->assertOk();
    }

    public function test_user_cannot_primary_default_type()
    {
        $this->signIn();

        $type = DocumentType::factory()->primary()->create();

        $this->deleteJson("/api/document-types/$type->id")->assertStatus(409);

        $this->assertDatabaseHas('document_types', ['id' => $type->id]);
    }

    public function test_user_cannot_delete_default_type()
    {
        $this->signIn();

        $type = DocumentType::factory()->create();
        DocumentType::setDefault($type->id);

        $this->deleteJson("/api/document-types/$type->id")->assertStatus(409);

        $this->assertDatabaseHas('document_types', ['id' => $type->id]);
    }

    public function test_user_cannot_delete_type_with_documents()
    {
        $this->signIn();

        $type = DocumentType::factory()->has(Document::factory())->create();

        $this->deleteJson("/api/document-types/$type->id")->assertStatus(409);

        $this->assertDatabaseHas('document_types', ['id' => $type->id]);
    }

    public function test_user_cant_create_document_with_restricted_visibility_type()
    {
        $this->asRegularUser()->signIn();

        $type = $this->newTypeFactoryWithVisibilityGroup('users', User::factory())->create();

        $this->postJson(
            '/api/documents',
            ['document_type_id' => $type->id]
        )
            ->assertJsonValidationErrors(['document_type_id' => 'This document type id value is forbidden.']);
    }

    public function test_user_cant_update_document_with_restricted_visibility_type()
    {
        $this->asRegularUser()->withPermissionsTo('edit all documents')->signIn();
        $document = Document::factory()->create();
        $type = $this->newTypeFactoryWithVisibilityGroup('users', User::factory())->create();

        $this->putJson(
            "/api/documents/$document->id",
            ['document_type_id' => $type->id]
        )
            ->assertJsonValidationErrors(['document_type_id' => 'This document type id value is forbidden.']);
    }

    protected function newTypeFactoryWithVisibilityGroup($group, $attached)
    {
        return DocumentType::factory()->has(
            ModelVisibilityGroup::factory()->{$group}()->hasAttached($attached),
            'visibilityGroup'
        );
    }
}
