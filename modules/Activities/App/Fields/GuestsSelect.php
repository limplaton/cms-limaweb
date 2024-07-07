<?php
 

namespace Modules\Activities\App\Fields;

use Modules\Activities\App\Http\Resources\GuestResource;
use Modules\Core\App\Fields\Field;
use Modules\Core\App\Http\Requests\ResourceRequest;
use Modules\Core\App\Models\Model;

class GuestsSelect extends Field
{
    /**
     * Field component.
     */
    public static $component = 'guests-select-field';

    /**
     * Additional relationships to eager load when quering the resource.
     */
    public array $with = ['guests.guestable'];

    /**
     * Indicates if the field is searchable.
     */
    protected bool $searchable = false;

    /**
     * Initialize new GuestsSelect instance.
     */
    public function __construct()
    {
        parent::__construct(...func_get_args());

        $this->toggleable()
            ->provideSampleValueUsing(fn () => [])
            ->fillUsing(function (Model $model, string $attribute, ResourceRequest $request, mixed $value, string $requestAttribute) {
                if (! is_array($value)) {
                    return;
                }

                return function () use ($model, $value, $request) {
                    $model->saveGuests($this->parseGuestsForSave($value, $request));
                };
            })->resolveForJsonResourceUsing(function (Model $model, string $attribute) {
                if ($model->relationLoaded('guests')) {
                    return ['guests' => GuestResource::collection($model->guests)];
                }
            });
    }

    /**
     * Resolve the displayable field value (for mail placeholders)
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return string|null
     */
    public function resolveForDisplay($model)
    {
        $value = parent::resolveForDisplay($model);

        if ($value->isNotEmpty()) {
            $value->loadMissing('guestable');

            return $value->map(fn ($guest) => $guest->guestable)->map->getGuestDisplayName()->implode(', ');
        }

        return null;
    }

    /**
     * Parse the given guests array for save
     */
    protected function parseGuestsForSave(array $guests, ResourceRequest $request): array
    {
        $parsed = [];

        foreach ($guests as $resourceName => $ids) {
            $parsed = array_merge(
                $parsed,
                $request->findResource($resourceName)->newQuery()->findMany($ids)->all()
            );
        }

        return $parsed;
    }
}
