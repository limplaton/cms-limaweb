<?php
 

namespace Modules\Core\App\Http\Resources;

use Illuminate\Http\Request;

/** @mixin \Modules\Core\App\Models\MailableTemplate */
class MailableTemplateResource extends JsonResource
{
    /**
     * Transform the resource collection into an array.
     */
    public function toArray(Request $request): array
    {
        return $this->withCommonData([
            'name' => $this->name,
            'locale' => $this->locale,
            'subject' => $this->getSubject(),
            'html_template' => clean($this->getHtmlTemplate()),
            'text_template' => clean($this->getTextTemplate()),
            'placeholders' => $this->getPlaceholders(),
        ], $request);
    }
}
