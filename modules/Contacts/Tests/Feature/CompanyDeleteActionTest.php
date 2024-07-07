<?php
 

namespace Modules\Contacts\Tests\Feature;

use Modules\Core\Tests\ResourceTestCase;
use Modules\Users\App\Models\User;

class CompanyDeleteActionTest extends ResourceTestCase
{
    protected $resourceName = 'companies';

    public function test_company_delete_action()
    {
        $this->signIn();

        $companies = $this->factory()->count(2)->create();

        $this->runAction('delete-action', $companies)->assertActionOk();
        $this->assertSoftDeleted('companies', ['id' => $companies->modelKeys()]);
    }

    public function test_unauthorized_user_cant_run_company_delete_action()
    {
        $this->asRegularUser()->signIn();

        $companies = $this->factory()->for(User::factory())->count(2)->create();

        $this->runAction('delete-action', $companies)->assertActionUnauthorized();
        $this->assertDatabaseHas('companies', ['id' => $companies->modelKeys()]);
    }

    public function test_authorized_user_can_run_company_delete_action()
    {
        $this->asRegularUser()->withPermissionsTo('delete any company')->signIn();

        $company = $this->factory()->for(User::factory())->create();

        $this->runAction('delete-action', $company)->assertActionOk();
        $this->assertSoftDeleted('companies', ['id' => $company->id]);
    }

    public function test_authorized_user_can_run_company_delete_action_only_on_own_companies()
    {
        $signedInUser = $this->asRegularUser()->withPermissionsTo('delete own companies')->signIn();

        $companyForSignedIn = $this->factory()->for($signedInUser)->create();
        $othercompany = $this->factory()->create();

        $this->runAction('delete-action', $othercompany)->assertActionUnauthorized();
        $this->assertDatabaseHas('companies', ['id' => $othercompany->id]);

        $this->runAction('delete-action', $companyForSignedIn);
        $this->assertSoftDeleted('companies', ['id' => $companyForSignedIn->id]);
    }

    public function test_authorized_user_can_bulk_delete_companies()
    {
        $this->asRegularUser()->withPermissionsTo([
            'delete any company', 'bulk delete companies',
        ])->signIn();

        $companies = $this->factory()->for(User::factory())->count(2)->create();

        $this->runAction('delete-action', $companies);
        $this->assertSoftDeleted('companies', ['id' => $companies->modelKeys()]);
    }

    public function test_authorized_user_can_bulk_delete_only_own_companies()
    {
        $signedInUser = $this->asRegularUser()->withPermissionsTo([
            'delete own companies',
            'bulk delete companies',
        ])->signIn();

        $companiesForSignedInUser = $this->factory()->count(2)->for($signedInUser)->create();
        $othercompany = $this->factory()->create();

        $this->runAction('delete-action', $companiesForSignedInUser->push($othercompany))->assertActionOk();
        $this->assertDatabaseHas('companies', ['id' => $othercompany->id]);
        $this->assertSoftDeleted('companies', ['id' => $companiesForSignedInUser->modelKeys()]);
    }

    public function test_unauthorized_user_cant_bulk_delete_companies()
    {
        $this->asRegularUser()->signIn();

        $companies = $this->factory()->count(2)->create();

        $this->runAction('delete-action', $companies)->assertActionUnauthorized();
        $this->assertDatabaseHas('companies', ['id' => $companies->modelKeys()]);
    }

    public function test_user_without_bulk_delete_permission_cannot_bulk_delete_companies()
    {
        $this->asRegularUser()->withPermissionsTo([
            'delete any company',
            'delete own companies',
            'delete team companies',
        ])->signIn();

        $companies = $this->factory()->for(User::factory())->count(2)->create();

        $this->runAction('delete-action', $companies)->assertActionUnauthorized();
        $this->assertDatabaseHas('companies', ['id' => $companies->modelKeys()]);
    }
}
