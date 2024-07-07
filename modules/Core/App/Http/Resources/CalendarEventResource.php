<?php
 

namespace Modules\Core\App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \Modules\Core\App\Contracts\Calendar\DisplaysOnCalendar */
class CalendarEventResource extends JsonResource
{
    /**
     * Transform the resource collection into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->getKey(),
            'title' => $this->getCalendarTitle($request->viewName),
            'start' => $this->getCalendarStartDate($request->viewName),
            'end' => $this->getCalendarEndDate($request->viewName),
            'allDay' => $this->isAllDay(),
            'isReadOnly' => $request->user()->cant('update', $this->resource),
            'extendedProps' => array_merge([
                'event_type' => strtolower(class_basename($this->resource)),
            ], method_exists($this->resource, 'getCalendarExtendedProps') ?
            $this->getCalendarExtendedProps() :
            []),
        ];
    }
}
