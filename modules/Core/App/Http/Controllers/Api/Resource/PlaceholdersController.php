<?php
 

namespace Modules\Core\App\Http\Controllers\Api\Resource;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\App\Facades\Innoclapps;
use Modules\Core\App\Http\Controllers\ApiController;
use Modules\Core\App\Resource\PlaceholdersGroup;
use Modules\Core\App\Resource\ResourcePlaceholders;

class PlaceholdersController extends ApiController
{
    /**
     * Retrieve placeholders via fields.
     */
    public function index(Request $request): JsonResponse
    {
        return $this->response(ResourcePlaceholders::createGroupsFromResources(
            $request->input('resources', [])
        ));
    }

    /**
     * Parse placeholders via input fields.
     */
    public function parseViaInputFields(Request $request): JsonResponse
    {
        $resources = $request->input('resources', []);

        return $this->response(
            $this->placeholders($resources, $request)->parseWhenViaInputFields($request->content)
        );
    }

    /**
     * Parse placeholders via interpolation.
     */
    public function parseViaInterpolation(Request $request): JsonResponse
    {
        $resources = $request->input('resources', []);

        return $this->response(
            $this->placeholders($resources, $request)->render($request->content)
        );
    }

    /**
     * Create new Placeholders instance from the given resources.
     */
    protected function placeholders(array $resources, Request $request): ResourcePlaceholders
    {
        $groups = [];

        foreach ($resources as $resource) {
            $instance = Innoclapps::resourceByName($resource['name']);

            if ($instance) {
                $record = $instance->displayQuery()->find($resource['id']);

                if ($request->user()->can('view', $record)) {
                    $groups[$resource['name']] = new PlaceholdersGroup($instance, $record);
                }
            }
        }

        return new ResourcePlaceholders(array_values($groups));
    }
}
