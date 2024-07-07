<?php
 

namespace Modules\Core\App\Fields;

use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Modules\Core\App\Http\Requests\ResourceRequest;
use Modules\Core\App\Models\Model;
use Modules\Core\App\Table\BelongsToColumn;
use Modules\Users\App\Http\Resources\UserResource;
use Modules\Users\App\Models\User as UserModel;

class User extends BelongsTo
{
    /**
     * The notification class name to be sent after user change.
     */
    public ?string $notification = null;

    /**
     * The date column name to track changed date.
     */
    public ?string $trackChangeDateColumn = null;

    /**
     * The assigneer.
     */
    public static ?UserModel $assigneer = null;

    /**
     * Creat new User instance field.
     *
     * @param  string  $label  Custom label
     * @param  string  $relationName
     * @param  string|null  $attribute
     */
    public function __construct($label = null, $relationName = 'user', $attribute = null)
    {
        parent::__construct($relationName, UserModel::class, $label ?: __('users::user.user'), $attribute);

        static::useIndexComponent('index-user-field');

        // Auth check for console usage
        $this->withDefaultValue(Auth::check() ? $this->makeOption(Auth::user()) : null)
            ->importRules($this->userFieldImportRules())
            ->setJsonResource(UserResource::class)
            ->fillUsing(function (Model $model, string $attribute, ResourceRequest $request, mixed $value, string $requestAttribute) {
                $model->{$attribute} = $value;

                $this->handleChangeColumnFill($model, $attribute, $value);

                return function () use ($model, $request) {
                    if ($this->notification) {
                        $this->handleUserChangeNotification($model, $request);
                    }
                };
            })->tapIndexColumn(function (BelongsToColumn $column) {
                $column->select('avatar')->appends('avatar_url');
            });
    }

    /**
     * Provide the User field options.
     */
    public function resolveOptions(): array
    {
        // The user field is the most used field in the APP,
        // in this case we will make sure to cache them in an array.
        return Cache::store('array')->rememberForever(
            'user-field-options',
            fn () => UserModel::select([$this->valueKey, $this->labelKey, 'avatar'])
                ->orderBy($this->labelKey)
                ->get()
                ->map($this->makeOption(...))
                ->all()
        );
    }

    /**
     * Set the user that perform the assignee.
     */
    public static function setAssigneer(?UserModel $user)
    {
        static::$assigneer = $user;
    }

    /**
     * Send a notification when the user changes.
     */
    public function notification(string $notification): static
    {
        $this->notification = $notification;

        return $this;
    }

    /**
     * Set date column to track the date when the user was changed.
     */
    public function trackChangeDate(string $dateColumn): static
    {
        $this->trackChangeDateColumn = $dateColumn;

        return $this;
    }

    /**
     * Handle the user change notification.
     */
    protected function handleUserChangeNotification(Model $model, ResourceRequest $request): void
    {
        /** @var \Modules\Users\App\Models\User */
        $assigneer = static::$assigneer ?? Auth::user();

        if ($id = $this->shouldSendNotification($model, $assigneer)) {
            // Retrieve new instance of the user, if we access the relation directly,
            // it may be cached by Laravel and the notification will be sent to the old user.
            UserModel::find($id)->notify(
                with($this->notification, fn ($notification) => new $notification($model, $assigneer))
            );
        }
    }

    /**
     * Fill the change column when the user has changed.
     */
    protected function handleChangeColumnFill(Model $model, string $attribute, mixed $value)
    {
        if ($this->trackChangeDateColumn) {
            if (! $model->exists) {
                if (! empty($value)) {
                    $model->{$this->trackChangeDateColumn} = now();
                }
            } else {
                if (empty($value)) {
                    $model->{$this->trackChangeDateColumn} = null;
                } elseif ($model->isDirty($attribute)) {
                    $model->{$this->trackChangeDateColumn} = now();
                }
            }
        }
    }

    /**
     * Check whether a notification should be sent for the given model and assigneer.
     */
    protected function shouldSendNotification(Model $model, ?UserModel $assigneer): int|bool
    {
        if (! $assigneer) {
            return false;
        }

        $currentId = $model->{$this->attribute};

        // Asssigned user not found
        if (! $currentId) {
            return false;
        }

        // Is update and is the same user
        if ((! $model->wasRecentlyCreated && ! $model->wasChanged($this->attribute))) {
            return false;
        }

        // The assigned user is the same as the logged in user
        if ($currentId && $currentId === Auth::id()) {
            return false;
        }

        // We will check if there an assigneer, if not, we won't send the notification
        // as well if the assigneer is the same like the actual user from the field
        if (! ($currentId && $assigneer->getKey() !== $currentId)) {
            return false;
        }

        return $currentId;
    }

    /**
     * Make option for the front-end.
     */
    protected function makeOption(UserModel $user): array
    {
        return [
            $this->valueKey => $user->{$this->valueKey},
            $this->labelKey => $user->{$this->labelKey},
            'avatar_url' => $user->avatar_url,
        ];
    }

    /**
     * Get the user import rules.
     */
    protected function userFieldImportRules(): array
    {
        return [function (string $attribute, mixed $value, Closure $fail) {
            if (! is_null($value)) {
                $this->getCachedOptions()->filter(function ($user) use ($value) {
                    return $user[$this->valueKey] == $value || $user[$this->labelKey] == $value;
                })->whenEmpty(
                    fn () => $fail('validation.import.user.invalid')->translate(['attribute' => $this->label])
                );
            }
        }];
    }
}
