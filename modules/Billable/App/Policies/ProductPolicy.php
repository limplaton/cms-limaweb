<?php
 

namespace Modules\Billable\App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Billable\App\Models\Product;
use Modules\Users\App\Models\User;

class ProductPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any products.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the product.
     */
    public function view(User $user, Product $product): bool
    {
        if ($user->can('view all products')) {
            return true;
        }

        if ((int) $product->created_by === (int) $user->id) {
            return true;
        }

        if ($user->can('view team products')) {
            return $user->managesAnyTeamsOf($product->created_by);
        }

        return false;
    }

    /**
     * Determine if the given user can create products.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the product.
     */
    public function update(User $user, Product $product): bool
    {
        if ($user->can('edit all products')) {
            return true;
        }

        if ($user->can('edit own products') && (int) $user->id === (int) $product->created_by) {
            return true;
        }

        if ($user->can('edit team products') && $user->managesAnyTeamsOf($product->created_by)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the product.
     */
    public function delete(User $user, Product $product): bool
    {
        if ($user->can('delete any product')) {
            return true;
        }

        if ($user->can('delete own products') && (int) $user->id === (int) $product->created_by) {
            return true;
        }

        if ($user->can('delete team products') && $user->managesAnyTeamsOf($product->created_by)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user bulk delete products.
     */
    public function bulkDelete(User $user, ?Product $product = null)
    {
        if (! $product) {
            return $user->can('bulk delete products');
        }

        if ($product && $user->can('bulk delete products')) {
            return $this->delete($user, $product);
        }

        return false;
    }

    /**
     * Determine whether the user can export products.
     */
    public function export(User $user): bool
    {
        return $user->can('export products');
    }
}
