<?php
 

namespace Modules\Core\App\Http\Controllers\Api;

use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\App\Contracts\Calendar\DisplaysOnCalendar;
use Modules\Core\App\Facades\Innoclapps;
use Modules\Core\App\Http\Controllers\ApiController;
use Modules\Core\App\Http\Resources\CalendarEventResource;
use Modules\Core\App\Resource\Resource;

class CalendarController extends ApiController
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $events = collect([]);

        foreach ($this->filterResourcesForCalendar($request) as $resource) {
            $query = $resource->newQuery();

            $events = $events->merge($this->applyQuery($query, $resource, $request)->get());
        }

        return $this->response(
            CalendarEventResource::collection($events)
        );
    }

    /**
     * Apply the calendar query
     */
    protected function applyQuery(Builder $query, Resource $resource, Request $request): Builder
    {
        $startColumn = $resource::$model::getCalendarStartColumnName();
        $endColumn = $resource::$model::getCalendarEndColumnName();

        $grammar = $query->getModel()->getConnection()->getQueryGrammar();

        $startColumnString = $startColumn instanceof Expression ? $startColumn->getValue($grammar) : $startColumn;
        $endColumnString = $endColumn instanceof Expression ? $endColumn->getValue($grammar) : $endColumn;

        $query = $query->whereRaw('? IS NOT NULL', $startColumn)
            ->whereRaw('? IS NOT NULL', $endColumn)
            ->where(function ($query) use ($startColumn, $startColumnString, $endColumnString, $endColumn, $request) {
                $query->where(function ($query) use ($startColumn, $startColumnString, $endColumnString, $request) {
                    // https://stackoverflow.com/questions/17014066/mysql-query-to-select-events-between-start-end-date
                    $spanRaw = "? between $startColumnString AND $endColumnString";

                    return $query->whereBetween($startColumn, [
                        $request->start_date,
                        $request->end_date,
                    ])->orWhereRaw($spanRaw, $request->start_date);
                });

                if (method_exists($query->getModel(), 'tapCalendarDateQuery')) {
                    $query->getModel()->tapCalendarDateQuery($query, $startColumn, $endColumn, $request);
                }
            });

        if (method_exists($query->getModel(), 'tapCalendarQuery')) {
            $query->getModel()->tapCalendarQuery($query, $request);
        }

        return $query;
    }

    /**
     * Filter the calendarable resources
     *
     * @return \Illuminate\Support\Collection<object, \Modules\Core\App\Resource\Resource>
     */
    protected function filterResourcesForCalendar(Request $request)
    {
        return Innoclapps::registeredResources()->filter(function ($resource) use ($request) {
            if (is_subclass_of($resource::$model, DisplaysOnCalendar::class)) {
                return $request->resource_name ? $resource->name() === $request->resource_name : true;
            }
        });
    }
}
