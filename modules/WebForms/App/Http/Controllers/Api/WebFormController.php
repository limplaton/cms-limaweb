<?php
 

namespace Modules\WebForms\App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\App\Http\Controllers\ApiController;
use Modules\Core\App\Rules\StringRule;
use Modules\Core\App\Rules\ValidLocaleRule;
use Modules\WebForms\App\Http\Resources\WebFormResource;
use Modules\WebForms\App\Models\WebForm;

class WebFormController extends ApiController
{
    /**
     * Get all web forms.
     */
    public function index(): JsonResponse
    {
        $forms = WebForm::withCommon()->get();

        return $this->response(
            WebFormResource::collection($forms)
        );
    }

    /**
     * Display web form.
     */
    public function show(WebForm $form): JsonResponse
    {
        return $this->response(
            new WebFormResource($form->loadMissing('user'))
        );
    }

    /**
     * Store a newly created web form in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $this->validateForm($request);

        $form = WebForm::create($request->all());

        return $this->response(
            new WebFormResource($form->loadMissing('user')),
            JsonResponse::HTTP_CREATED
        );
    }

    /**
     * Update the specified web form in storage.
     */
    public function update(WebForm $form, Request $request): JsonResponse
    {
        $this->validateForm($request);

        $form->fill($request->all())->save();

        return $this->response(
            new WebFormResource($form->loadMissing('user'))
        );
    }

    /**
     * Remove the specified web form from storage.
     */
    public function destroy(WebForm $form): JsonResponse
    {
        $form->delete();

        return $this->response('', JsonResponse::HTTP_NO_CONTENT);
    }

    /**
     * Validate the request.
     */
    protected function validateForm(Request $request): void
    {
        $request->validate([
            'locale' => ['sometimes', 'required', 'string', new ValidLocaleRule],
            'title' => array_filter([$request->isMethod('PUT') ? 'sometimes' : null, 'required', StringRule::make()]),
            'styles.primary_color' => array_filter([$request->isMethod('PUT') ? 'sometimes' : null, 'required', 'hex_color']),
            'styles.background_color' => array_filter([$request->isMethod('PUT') ? 'sometimes' : null, 'required', 'hex_color']),
            'styles.logo' => 'sometimes|nullable|in:dark,light',
            'notifications.*' => ['filled', StringRule::make(), 'email'],
            'submit_data.pipeline_id' => 'sometimes|required',
            'submit_data.stage_id' => 'sometimes|required',
            'submit_data.action' => 'sometimes|required|in:message,redirect',
            'submit_data.success_title' => 'required_if:submit_data.action,message',
            'submit_data.success_redirect_url' => 'nullable|required_if:submit_data.action,redirect|url',
            'user_id' => 'sometimes|required',
        ]);
    }
}
