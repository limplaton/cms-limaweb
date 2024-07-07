<?php
 

namespace Modules\WebForms\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Modules\WebForms\App\Http\Requests\WebFormRequest;
use Modules\WebForms\App\Http\Resources\WebFormResource;
use Modules\WebForms\App\Models\WebForm;
use Modules\WebForms\App\Services\FormSubmissionService;

class WebFormController extends Controller
{
    /**
     * Display the web form.
     */
    public function show(string $uuid, Request $request): View
    {
        $form = WebForm::findByUuid($uuid);

        // Change the locale in case the fields are using the translation
        // function so the data can be properly shown
        // @todo, check this, perhaps not needed?
        app()->setLocale($form->locale);

        $form->addFieldToFieldSections();

        abort_if(! Auth::check() && ! $form->isActive(), 404);

        $form = new WebFormResource($form);
        $title = $form->sections[0]['title'] ?? __('webforms::form.form');

        return view('webforms::view', compact('form', 'title'));
    }

    /**
     * Process the webform request.
     */
    public function store(string $uuid, FormSubmissionService $service, WebFormRequest $request): JsonResponse
    {
        $request->performValidation();

        $service->submit($request);

        return response()->json('', 204);
    }
}
