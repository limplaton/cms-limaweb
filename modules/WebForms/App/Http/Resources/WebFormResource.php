<?php
 

namespace Modules\WebForms\App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Users\App\Http\Resources\UserResource;
use Modules\WebForms\App\Enums\WebFormSection;

/** @mixin \Modules\WebForms\App\Models\WebForm */
class WebFormResource extends JsonResource
{
    /**
     * Transform the resource collection into an array.
     */
    public function toArray(Request $request): array
    {
        $cleanSubmitData = ['success_message'];

        $cleanSectionsData = [
            WebFormSection::INTRODUCTION->value => ['message'],
            WebFormSection::MESSAGE->value => ['message'],
            WebFormSection::FIELD->value => function (&$section) {
                $section['label'] = clean($section['label']);
            },
            WebFormSection::FILE->value => function (&$section) {
                $section['label'] = clean($section['label']);
            },
        ];

        return [
            'id' => $this->id,
            'title' => $this->title,
            'status' => $this->status,
            'uuid' => $this->uuid,
            'sections' => collect($this->sections)
                ->map(function (array $section) use ($cleanSectionsData) {
                    if (array_key_exists($section['type'], $cleanSectionsData)) {
                        if (is_callable($cleanSectionsData[$section['type']])) {
                            $cleanSectionsData[$section['type']]($section);
                        } else {
                            foreach ($cleanSectionsData[$section['type']] as $key) {
                                $section = array_merge($section, [$key => clean($section[$key])]);
                            }
                        }
                    }

                    return $section;
                }),
            'submit_data' => collect($this->submit_data)
                ->mapWithKeys(function ($value, $key) use ($cleanSubmitData) {
                    if (in_array($key, $cleanSubmitData)) {
                        $value = clean($value);
                    }

                    return [$key => $value];
                }),
            'styles' => $this->styles,
            'notifications' => $this->notifications,
            'title_prefix' => $this->title_prefix,
            'locale' => $this->locale,
            'user_id' => $this->user_id,
            'user' => new UserResource($this->whenLoaded('user')),
            'public_url' => $this->publicUrl,
            'total_submissions' => $this->total_submissions,
        ];
    }
}
