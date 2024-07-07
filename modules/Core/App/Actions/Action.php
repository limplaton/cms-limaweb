<?php
 

namespace Modules\Core\App\Actions;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use JsonSerializable;
use Modules\Core\App\Facades\Innoclapps;
use Modules\Core\App\Fields\FieldsCollection;
use Modules\Core\App\Http\Requests\ActionRequest;
use Modules\Core\App\Http\Requests\ResourceRequest;
use Modules\Core\App\Support\Authorizeable;

abstract class Action implements JsonSerializable
{
    use Authorizeable;

    /**
     * Indicates that the action will be hidden on the index view.
     */
    public bool $hideOnIndex = false;

    /**
     * Indicates that the action will be hidden on the detail view.
     */
    public bool $hideOnDetail = false;

    /**
     * Indicates that the action does not have confirmation dialog.
     */
    public bool $withoutConfirmation = false;

    /**
     * Action name
     */
    public ?string $name = null;

    /**
     * The action modal size. (sm, md, lg, xl, xxl)
     */
    public string $size = 'sm';

    /**
     * The count of the models user selected for the action.
     */
    public int $total = 0;

    /**
     * The XHR response type that should be passed from the front-end.
     */
    public string $responseType = 'json';

    /**
     * Determine if the action is executable for the given request.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     */
    abstract public function authorizedToRun(ActionRequest $request, $model): bool;

    /**
     * Handle method that all actions must implement.
     *
     * @return mixed
     */
    public function handle(Collection $models, ActionFields $fields)
    {
        return [];
    }

    /**
     * Get the action fields.
     */
    public function fields(ResourceRequest $request): array
    {
        return [];
    }

    /**
     * Resolve action fields.
     *
     * @return \Modules\Core\App\Fields\FieldsCollection
     */
    public function resolveFields(ResourceRequest $request)
    {
        return (new FieldsCollection($this->fields($request)))->authorized();
    }

    /**
     * Run action based on the request data.
     *
     * @return mixed
     */
    public function run(ActionRequest $request, Builder $query)
    {
        $ids = $request->input('ids', []);
        $fields = $request->resolveFields();

        $this->total = count($ids);

        /**
         * Find all models and exclude any models that are not authorized to be handled in this action
         */
        $models = $this->filterForExecution(
            $this->findModelsForExecution($ids, $query),
            $request
        );

        /**
         * All models excluded? In this case, the user is probably not authorized to run the action
         */
        if ($models->count() === 0) {
            return static::error(__('users::user.not_authorized'));
        } elseif ($models->count() > (int) config('core.actions.disable_notifications_more_than')) {
            Innoclapps::muteAllCommunicationChannels();
        }

        $response = $this->handle($models, $fields);

        if (! is_null($response)) {
            return $response;
        }

        return static::success(__('core::actions.run_successfully'));
    }

    /**
     * Set the action modal size.
     */
    public function size(string $size): static
    {
        $this->size = $size;

        return $this;
    }

    /**
     * Toasted success alert.
     */
    public static function success(string $message): array
    {
        return ['success' => $message];
    }

    /**
     * Toasted info alert.
     */
    public static function info(string $message): array
    {
        return ['info' => $message];
    }

    /**
     * Toasted success alert.
     */
    public static function error(string $message): array
    {
        return ['error' => $message];
    }

    /**
     * Throw confetti on success.
     */
    public static function confetti(): array
    {
        return ['confetti' => true];
    }

    /**
     * Return an open new tab response from the action.
     */
    public static function openInNewTab(string $url): array
    {
        return ['openInNewTab' => $url];
    }

    /**
     * Provide action human readable name.
     */
    public function name(): string
    {
        return $this->name ?: Str::title(Str::snake(get_called_class(), ' '));
    }

    /**
     * Set the action name.
     */
    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get the URI key for the card.
     */
    public function uriKey(): string
    {
        return Str::kebab(class_basename(get_called_class()));
    }

    /**
     * Message shown when performing the action.
     */
    public function message(): string
    {
        return __('core::actions.confirmation_message');
    }

    /**
     * Set the action to not have confirmation dialog.
     */
    public function withoutConfirmation(): static
    {
        $this->withoutConfirmation = true;

        return $this;
    }

    /**
     * Get the component the action should use.
     */
    public function component(): string
    {
        return 'action-dialog';
    }

    /**
     * Set the action to be available only on index view.
     */
    public function onlyOnIndex(): static
    {
        $this->hideOnDetail = true;
        $this->hideOnIndex = false;

        return $this;
    }

    /**
     * Set the action to be available only on detail view.
     */
    public function onlyOnDetail(): static
    {
        $this->hideOnDetail = false;
        $this->hideOnIndex = true;

        return $this;
    }

    /**
     * Query the models for execution
     */
    protected function findModelsForExecution(array $ids, Builder $query): EloquentCollection
    {
        return $query->findMany($ids);
    }

    /**
     * Filter models for exeuction.
     */
    public function filterForExecution(Collection $models, ActionRequest $request): Collection
    {
        return $models->filter(fn ($model) => $this->authorizedToRun($request, $model));
    }

    /**
     * jsonSerialize.
     */
    public function jsonSerialize(): array
    {
        return [
            'name' => $this->name(),
            'message' => $this->message(),
            'component' => $this->component(),
            'destroyable' => $this instanceof DestroyableAction,
            'withoutConfirmation' => $this->withoutConfirmation,
            'fields' => $this->resolveFields(app(ResourceRequest::class)),
            'hideOnIndex' => $this->hideOnIndex,
            'hideOnDetail' => $this->hideOnDetail,
            'responseType' => $this->responseType,
            'size' => $this->size,
            'uriKey' => $this->uriKey(),
        ];
    }
}
