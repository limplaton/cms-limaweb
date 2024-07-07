<?php
 

namespace Modules\Core\App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\App\Facades\MailableTemplates;
use Modules\Core\App\Http\Controllers\ApiController;
use Modules\Core\App\Http\Resources\MailableTemplateResource;
use Modules\Core\App\Models\MailableTemplate;
use Modules\Core\App\Rules\StringRule;

class MailableTemplateController extends ApiController
{
    /**
     * Retrieve all of the available mailable templates.
     */
    public function index(): JsonResponse
    {
        MailableTemplates::seed();

        $collection = MailableTemplateResource::collection(MailableTemplate::orderBy('name')->get());

        return $this->response($collection);
    }

    /**
     * Retrieve mail templates in specific locale.
     */
    public function forLocale(string $locale): JsonResponse
    {
        MailableTemplates::seed();

        $collection = MailableTemplateResource::collection(
            MailableTemplate::orderBy('name')->forLocale($locale)->get()
        );

        return $this->response($collection);
    }

    /**
     * Display the specified resource.
     */
    public function show(MailableTemplate $template): JsonResponse
    {
        return $this->response(new MailableTemplateResource($template));
    }

    /**
     * Update the specified mail template in storage.
     */
    public function update(MailableTemplate $template, Request $request): JsonResponse
    {
        $request->validate([
            'subject' => ['required', StringRule::make()],
            'html_template' => 'required|string',
        ]);

        $template->fill($request->all())->save();

        return $this->response(new MailableTemplateResource($template));
    }
}
