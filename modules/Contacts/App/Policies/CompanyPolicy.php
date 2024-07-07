<?php
 

namespace Modules\Contacts\App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Contacts\App\Models\Company;
use Modules\Users\App\Models\User;

class CompanyPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any companies.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the company.
     */
    public function view(User $user, Company $company): bool
    {
        if ($user->can('view all companies')) {
            return true;
        }

        if ((int) $company->user_id === (int) $user->id) {
            return true;
        }

        if ($company->user_id && $user->can('view team companies')) {
            return $user->managesAnyTeamsOf($company->user_id);
        }

        return false;
    }

    /**
     * Determine if the given user can create companies.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the company.
     */
    public function update(User $user, Company $company): bool
    {
        if ($user->can('edit all companies')) {
            return true;
        }

        if ($user->can('edit own companies') && (int) $user->id === (int) $company->user_id) {
            return true;
        }

        if ($company->user_id && $user->can('edit team companies') && $user->managesAnyTeamsOf($company->user_id)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the company.
     */
    public function delete(User $user, Company $company): bool
    {
        if ($user->can('delete any company')) {
            return true;
        }

        if ($user->can('delete own companies') && (int) $user->id === (int) $company->user_id) {
            return true;
        }

        if ($company->user_id && $user->can('delete team companies') && $user->managesAnyTeamsOf($company->user_id)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user bulk delete companies.
     */
    public function bulkDelete(User $user, ?Company $company = null)
    {
        if (! $company) {
            return $user->can('bulk delete companies');
        }

        if ($company && $user->can('bulk delete companies')) {
            return $this->delete($user, $company);
        }

        return false;
    }

    /**
     * Determine whether the user can export companies.
     */
    public function export(User $user): bool
    {
        return $user->can('export companies');
    }
}
