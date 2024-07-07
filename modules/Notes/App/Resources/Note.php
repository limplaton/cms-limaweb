<?php
 

namespace Modules\Notes\App\Resources;

use Modules\Comments\App\Contracts\HasComments;
use Modules\Core\App\Contracts\Resources\HasOperations;
use Modules\Core\App\Criteria\RelatedCriteria;
use Modules\Core\App\Fields\Editor;
use Modules\Core\App\Http\Requests\ResourceRequest;
use Modules\Core\App\Resource\Resource;
use Modules\Notes\App\Http\Resources\NoteResource;

class Note extends Resource implements HasComments, HasOperations
{
    /**
     * The model the resource is related to
     */
    public static string $model = 'Modules\Notes\App\Models\Note';

    /**
     * Get the json resource that should be used for json response
     */
    public function jsonResource(): string
    {
        return NoteResource::class;
    }

    /**
     * Provide the available resource fields
     */
    public function fields(ResourceRequest $request): array
    {
        return [
            Editor::make('body')->rules(['required', 'string'])->onlyOnForms(),
        ];
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
     * Get the resource relationship name when it's associated
     */
    public function associateableName(): string
    {
        return 'notes';
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
     * Get the custom validation messages for the resource
     */
    public function validationMessages(): array
    {
        return [
            'body.required' => __('validation.required_without_label'),
        ];
    }

    /**
     * Get the displayable label of the resource
     */
    public static function label(): string
    {
        return __('notes::note.notes');
    }

    /**
     * Get the displayable singular label of the resource
     */
    public static function singularLabel(): string
    {
        return __('notes::note.note');
    }
}
