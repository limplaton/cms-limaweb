<?php
 

namespace Modules\Calls\App\Resources;

use Modules\Calls\App\Http\Resources\CallOutcomeResource;
use Modules\Calls\App\Http\Resources\CallResource;
use Modules\Calls\App\Models\CallOutcome;
use Modules\Comments\App\Contracts\HasComments;
use Modules\Core\App\Contracts\Resources\HasOperations;
use Modules\Core\App\Criteria\RelatedCriteria;
use Modules\Core\App\Facades\Innoclapps;
use Modules\Core\App\Fields\BelongsTo;
use Modules\Core\App\Fields\DateTime;
use Modules\Core\App\Fields\Editor;
use Modules\Core\App\Http\Requests\ResourceRequest;
use Modules\Core\App\Resource\Resource;
use Modules\Core\App\Settings\SettingsMenuItem;
use Modules\Core\App\Support\Carbon;

class Call extends Resource implements HasComments, HasOperations
{
    /**
     * The model the resource is related to
     */
    public static string $model = 'Modules\Calls\App\Models\Call';

    /**
     * Get the json resource that should be used for json response
     */
    public function jsonResource(): string
    {
        return CallResource::class;
    }

    /**
     * Provide the criteria that should be used to query only records that the logged-in user is authorized to view
     */
    public function viewAuthorizedRecordsCriteria(): ?string
    {
        if (! auth()->user()->isSuperAdmin()) {
            return RelatedCriteria::class;
        }

        return null;
    }

    /**
     * Set the available resource fields
     */
    public function fields(ResourceRequest $request): array
    {
        return [
            BelongsTo::make('outcome', CallOutcome::class, __('calls::call.outcome.outcome'))
                ->rules(['required', 'numeric'])
                ->setJsonResource(CallOutcomeResource::class)
                ->showValueWhenUnauthorizedToView() // when viewing related record e.q. deal
                ->options(Innoclapps::resourceByModel(CallOutcome::class))
                ->width('half')
                ->withMeta([
                    'attributes' => [
                        'clearable' => false,
                        'placeholder' => __('calls::call.outcome.select_outcome'),
                    ],
                ]),

            DateTime::make('date', __('calls::call.date'))
                ->withDefaultValue(Carbon::parse())
                ->width('half')
                ->rules('required'),

            Editor::make('body')
                ->rules(['required', 'string'])
                ->validationMessages(['required' => __('validation.required_without_label')])
                ->withMentions()
                ->minimal()
                ->withMeta([
                    'attributes' => [
                        'placeholder' => __('calls::call.log'),
                    ],
                ]),
        ];
    }

    /**
     * Get the resource available cards
     */
    public function cards(): array
    {
        return [
            (new \Modules\Calls\App\Cards\LoggedCallsByDay)->withUserSelection()->canSeeWhen('is-super-admin'),
            (new \Modules\Calls\App\Cards\TotalLoggedCallsBySaleAgent)->canSeeWhen('is-super-admin')->color('success'),
            (new \Modules\Calls\App\Cards\LoggedCalls)->canSeeWhen('is-super-admin')->withUserSelection(),
            (new \Modules\Calls\App\Cards\OverviewByCallOutcome)->color('info')->withUserSelection(function () {
                return auth()->user()->isSuperAdmin();
            }),
        ];
    }

    /**
     * Get the resource relationship name when it's associated
     */
    public function associateableName(): string
    {
        return 'calls';
    }

    /**
     * Get the resource rules available for create and update
     */
    public function rules(ResourceRequest $request): array
    {
        return [
            'via_resource' => ['required', 'in:contacts,companies,deals', 'string'],
            'via_resource_id' => ['required', 'numeric'],
        ];
    }

    /**
     * Register the settings menu items for the resource
     */
    public function settingsMenu(): array
    {
        return [
            SettingsMenuItem::make(__('calls::call.calls'), '/settings/calls', 'DeviceMobile')->order(25),
        ];
    }

    /**
     * Get the displayable label of the resource
     */
    public static function label(): string
    {
        return __('calls::call.calls');
    }

    /**
     * Get the displayable singular label of the resource
     */
    public static function singularLabel(): string
    {
        return __('calls::call.call');
    }
}
