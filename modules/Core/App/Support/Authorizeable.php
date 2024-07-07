<?php
 

namespace Modules\Core\App\Support;

use Closure;
use Illuminate\Support\Facades\Auth;
use Modules\Users\App\Models\User;

trait Authorizeable
{
    /**
     * Hold the canSee method closure.
     */
    public ?Closure $canSeeClosure = null;

    /**
     * Hold the canSeeWhen method data.
     */
    public ?array $canSeeWhenArrayClosure = null;

    /**
     * canSee method to perform checks on specific class.
     */
    public function canSee(Closure $callable): static
    {
        $this->canSeeClosure = $callable;

        return $this;
    }

    /**
     * canSeeWhen, the same signature like user()->can().
     *
     * @param  string  $ability  the ability
     * @param  array  $arguments
     */
    public function canSeeWhen($ability, $arguments = []): static
    {
        $this->canSeeWhenArrayClosure = [$ability, $arguments];

        return $this;
    }

    /**
     * Authorize or fail
     */
    public function authorizeOrFail(?string $message = null): static
    {
        if (! $this->authorizedToSee()) {
            abort(403, $message ?? 'You are not authorized to perform this action.');
        }

        return $this;
    }

    /**
     * Check whether the user can see a specific item
     *
     * @return bool|null
     */
    public function authorizedToSee(?User $user = null)
    {
        if (! $this->hasAuthorization()) {
            return true;
        }

        if ($this->canSeeWhenArrayClosure) {
            /** @var \Modules\Users\App\Models\User */
            $user = $user ?? Auth::user();

            return $user?->can(...$this->canSeeWhenArrayClosure);
        }

        return call_user_func($this->canSeeClosure, request());
    }

    /**
     * Check whether on specific class/item is added authorization via canSee and canSeeWhen
     */
    public function hasAuthorization(): bool
    {
        return $this->canSeeClosure || $this->canSeeWhenArrayClosure;
    }
}
