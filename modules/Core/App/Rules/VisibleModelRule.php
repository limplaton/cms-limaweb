<?php
 

namespace Modules\Core\App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Modules\Core\App\Common\VisibilityGroup\HasVisibilityGroups;
use Modules\Users\App\Models\User;

class VisibleModelRule implements ValidationRule
{
    protected mixed $ignore = null;

    /**
     * Create a new rule instance.
     */
    public function __construct(protected HasVisibilityGroups&Model $model, protected ?User $user = null)
    {
    }

    /**
     * Ignore the given ID from visiblity checks.
     */
    public function ignore(mixed $id): static
    {
        $this->ignore = $id;

        return $this;
    }

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (empty($value) || $this->isIgnoringValue($value)) {
            return;
        }

        if ($this->isNotVisible($value)) {
            $fail('This :attribute value is forbidden.');
        }
    }

    /**
     * Check whether is ignoring the current value from visiblity checks.
     */
    protected function isIgnoringValue($value): bool
    {
        $id = $this->ignore;

        if (! $id) {
            return false;
        }

        if ($id instanceof Closure) {
            $id = $id();
        }

        if ($id instanceof Model) {
            $id = $id->getKey();
        }

        return $id == $value;
    }

    /**
     * Check whether the given model ID is visible to the current user
     */
    protected function isNotVisible(string|int $id): bool
    {
        return $this->model->query()
            ->select($this->model->getKeyName())
            ->where($this->model->getKeyName(), $id)
            ->visible($this->user ?: Auth::user())
            ->count() === 0;
    }
}
